<?php

namespace Oro\UpgradeToolkit\YmlFixer\Manipilator;

use Psr\Log\LoggerInterface;
use SebastianBergmann\Diff\Differ;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

/**
 * Modified copy of \Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator, Symfony MakerBundle package
 *
 * Copyright (c) 2004-2020 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 *
 *
 * A class that modifies YAML source, while keeping comments & formatting.
 *
 * This is designed to work for the most common syntaxes, but not
 * all YAML syntaxes. If content cannot be updated safely, an
 * exception is thrown.
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class YamlSourceManipulator
{
    // @codingStandardsIgnoreStart
    public const EMPTY_LINE_PLACEHOLDER_VALUE = '__EMPTY_LINE__';
    public const COMMENT_PLACEHOLDER_VALUE = '__COMMENT__';

    public const UNSET_KEY_FLAG = '__MAKER_VALUE_UNSET';
    public const ARRAY_FORMAT_MULTILINE = 'multi';
    public const ARRAY_FORMAT_INLINE = 'inline';

    public const ARRAY_TYPE_SEQUENCE = 'sequence';
    public const ARRAY_TYPE_HASH = 'hash';

    /**
     * @var LoggerInterface|null
     */
    private $logger;
    private $currentData;

    private $currentPosition = 0;
    private $previousPath = [];
    private $currentPath = [];
    private $depth = 0;
    private $indentationForDepths = [];
    private $arrayFormatForDepths = [];
    private $arrayTypeForDepths = [];
    private Differ $differ;
    private bool $isContentsPreProcessed = false;

    public function __construct(
        private string $contents,
    ) {
        $this->currentData = $this->parse($contents);

        if (!\is_array($this->currentData)) {
            throw new \InvalidArgumentException('Only YAML with a top-level array structure is supported');
        }

        $this->differ = new Differ();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getData(): array
    {
        return $this->currentData;
    }

    public function getContents(): string
    {
        return $this->postProcessInput($this->contents);
    }

    public function setData(array $newData)
    {
        $this->currentPath = [];
        $this->previousPath = [];
        $this->currentPosition = 0;
        $this->depth = -1;
        $this->indentationForDepths = [];
        $this->arrayFormatForDepths = [];
        $this->arrayTypeForDepths = [];

        $this->updateData($newData);
        $this->replaceSpecialMetadataCharacters();
        // update the data now that the special chars have been removed
        $this->currentData = $this->parse($this->contents);

        // remove special metadata keys that were replaced
        $newData = $this->removeMetadataKeys($newData);

        // Commented out as a fix of unexpected replacement of integer keys
        // E.g.
        // -   200 => false,
        // -   403 => 'Returned always',
        // +   0 => false,
        // +   1 => 'Returned always',
        // $newData = $this->normalizeSequences($newData);

        if ($newData !== $this->currentData) {
            throw new YamlManipulationFailedException(
                sprintf(
                    "Fail: the process was successful, but something was not updated.\nDiff:\n%s",
                    $this->differ->diff(
                        var_export($this->currentData, true),
                        var_export($newData, true)
                    )
                )
            );
        }
    }

    public function createEmptyLine(): string
    {
        return self::EMPTY_LINE_PLACEHOLDER_VALUE;
    }

    public function createCommentLine(string $comment): string
    {
        return self::COMMENT_PLACEHOLDER_VALUE.$comment;
    }

    private function updateData(array $newData)
    {
        ++$this->depth;
        if (0 === $this->depth) {
            $this->indentationForDepths[$this->depth] = 0;
            $this->arrayFormatForDepths[$this->depth] = self::ARRAY_FORMAT_MULTILINE;
        } else {
            // match the current indentation to start
            $this->indentationForDepths[$this->depth] = $this->indentationForDepths[$this->depth - 1];
            // advancing is especially important if this is an inline array:
            // get into the [] or {}
            $this->arrayFormatForDepths[$this->depth] = $this->guessNextArrayTypeAndAdvance();
        }

        $currentData = $this->getCurrentData();

        $this->arrayTypeForDepths[$this->depth] = $this->isHash($currentData) ? self::ARRAY_TYPE_HASH : self::ARRAY_TYPE_SEQUENCE;

        $this->log(sprintf(
            'Changing array type & format via updateData() (type=%s, format=%s)',
            $this->arrayTypeForDepths[$this->depth],
            $this->arrayFormatForDepths[$this->depth]
        ));

        foreach ($currentData as $key => $currentVal) {
            // path setting is mostly duplicated at the bottom of this method
            $this->previousPath = $this->currentPath;
            if (!isset($this->previousPath[$this->depth])) {
                // if there is no previous flag at this level, mark it with a null
                $this->previousPath[$this->depth] = null;
            }
            $this->currentPath[$this->depth] = $key;

            // advance from the end of the previous value to the
            // start of the key, which may include whitespace or, for
            // example, some closing array syntax - } or ] - from the
            // previous value
            $this->advanceBeyondEndOfPreviousKey($key);

            $this->log('START key', true);

            // 1) was this key removed from the new data?
            if (!\array_key_exists($key, $newData)) {
                $this->log('Removing key');
                $this->removeKeyFromYaml($key, $currentData[$key]);

                // manually update our current data now that the key is gone
                unset($currentData[$key]);

                // because the item was removed, reset the current path
                // to the previous path, so the next iteration doesn't
                // expect the previous path to have this removed key
                $this->currentPath = $this->previousPath;

                continue;
            }

            /*
             * 2) are there new keys in the new data before this key?
             *
             * To determine this, we look at the position of the key inside the
             * current data and compare it to the position of that same key in
             * the new data. While they are not equal, we loop. Inside the loop,
             * the new key is added to the current data *before* $key. Thanks to
             * this, on each loop, the currentDataIndex will increase until it
             * matches the new data
             */
            while (($currentDataIndex = array_search($key, array_keys($currentData))) !== array_search($key, array_keys($newData))) {
                // loop until the current key is found at the same position in current & new data
                $newKey = array_keys($newData)[$currentDataIndex];
                $newVal = $newData[$newKey];
                $this->log('Adding new key: '.$newKey);

                $this->addNewKeyToYaml($newKey, $newVal);

                // refresh the current array data because we added an item
                // we can't just add the key manually, as it may have been
                // we can't just add the key manually, as it may have been
                // added in the middle
                $currentData = $this->getCurrentData(1);
            }

            // 3) Key already exists in YAML
            // advance the position to the end of this key
            $this->advanceBeyondKey($key);
            $newVal = $newData[$key];

            // if the current data is an array, we should keep
            // walking through that data, even if it didn't change,
            // so that we can advance the current position
            if (\is_array($currentData[$key]) && \is_array($newVal)) {
                $this->log('Calling updateData() on next level');
                $this->updateData($newVal);

                continue;
            }

            // 3a) value did NOT change
            if ($currentData[$key] === $newVal) {
                $this->log('value did not change');
                $this->advanceBeyondValue($newVal);

                continue;
            }

            // 3b) value DID change
            $this->log(sprintf('updating value to {%s}', \is_array($newVal) ? '<array>' : $newVal));
            $this->changeValueInYaml($newVal);
        }

        // Bonus! are there new keys in the data after this key...
        // and this is the final key?

        // Edge case: if the last item on a multi-line array has a comment,
        // we want to move to the end of the line, beyond that comment
        if (\count($currentData) < \count($newData) && $this->isCurrentArrayMultiline()) {
            $this->advanceBeyondMultilineArrayLastItem();
        }

        if (0 === $this->indentationForDepths[$this->depth] && $this->depth > 1) {
            $ident = $this->getPreferredIndentationSize();
            $previousDepth = $this->depth - 1;

            $this->indentationForDepths[$this->depth] = ($ident + $this->indentationForDepths[$previousDepth]);
        }

        while (\count($currentData) < \count($newData)) {
            $newKey = array_keys($newData)[\count($currentData)];

            // manually move the paths forward
            // mostly duplicated above
            $this->previousPath = $this->currentPath;
            if (!isset($this->previousPath[$this->depth])) {
                // if there is no previous flag at this level, mark it with a null
                $this->previousPath[$this->depth] = null;
            }
            $this->currentPath[$this->depth] = $newKey;

            $newVal = $newData[$newKey];
            $this->log('Adding new key to end of array');

            $this->addNewKeyToYaml($newKey, $newVal);

            // refresh manually so the while sees it above
            $currentData = $this->getCurrentData(1);
        }

        $this->decrementDepth();
    }

    /**
     * Adds a new key to current position in the YAML.
     *
     * The position should be set *right* where this new key
     * should be inserted.
     */
    private function addNewKeyToYaml($key, $value)
    {
        $extraOffset = 0;
        $firstItemInArray = false;
        // Modification
        // Fixed indentation issue of the new first element in multiline keyed arrays like:
        // root:
        //     key:
        //         subKeyOne: 'value 1'
        //         subKeyTwo: 'value 2'
        //     ...
        if (!empty($this->getCurrentData(1))
            && null !== $this->previousPath[$this->depth]
            && $this->indentationForDepths[$this->depth] === $this->indentationForDepths[$this->depth - 1]
        ) {
            $this->previousPath[$this->depth] = null;
        }

        if (empty($this->getCurrentData(1))) {
            // The array that we're appending is empty:

            // First, fix the "type" - it could be changing from a sequence to a hash or vice versa
            $this->arrayTypeForDepths[$this->depth] = \is_int($key) ? self::ARRAY_TYPE_SEQUENCE : self::ARRAY_TYPE_HASH;

            // we prefer multi-line, so let's convert to it!
            $this->arrayFormatForDepths[$this->depth] = self::ARRAY_FORMAT_MULTILINE;

            // if this is an inline empty array (is there any other), we need to
            // remove the empty array characters = {} or []

            // we are already 1 character beyond the starting { or [ - so, rewind before it
            --$this->currentPosition;
            // now, rewind any spaces to get back to the : after the key
            while (' ' === substr($this->contents, $this->currentPosition - 1, 1)) {
                --$this->currentPosition;
            }

            // determine an extra offset to "skip" when reconstructing the string
            $endingArrayPosition = $this->findPositionOfNextCharacter(['}', ']']);
            $extraOffset = $endingArrayPosition - $this->currentPosition;

            // increase the indentation of *this* level
            $this->manuallyIncrementIndentation();

            $firstItemInArray = true;
        } elseif ($this->isPositionAtBeginningOfArray()) {
            //} elseif (null !== $this->previousPath[$this->depth]) {
            $firstItemInArray = true;

            // the array is not empty, but we are prepending an element
            if ($this->isCurrentArrayMultiline()) {
                // indentation will be set to low, except for root level
                if ($this->depth > 0) {
                    $this->manuallyIncrementIndentation();
                }
            } else {
                // we're at the start of an inline array
                // advance beyond any whitespace so that our new key
                // uses the same whitespace that was originally after
                // the { or [
                $this->advanceBeyondWhitespace();
            }
        }

        if (\is_int($key)) {
            if ($this->isCurrentArrayMultiline()) {
                if ($this->isCurrentArraySequence()) {
                    $newYamlValue = '- '.$this->convertToYaml($value);
                } else {
                    // this is an associative array, but an indexed key
                    // is being added. We can't use the "- " format
                    $newYamlValue = sprintf(
                        '%s: %s',
                        $key,
                        $this->convertToYaml($value)
                    );
                }
            } else {
                $newYamlValue = $this->convertToYaml($value);
            }
        } else {
            $newYamlValue = $this->convertToYaml([$key => $value]);
        }

        if (0 === $this->currentPosition) {
            // if we're at the beginning of the file, the situation is special:
            // no previous blank line is needed, but we DO need to add a blank
            // line after, because the remainder of the content expects the
            // current position the start at the beginning of a new line
            $newYamlValue .= "\n";
        } else {
            if ($this->isCurrentArrayMultiline()) {
                // because we're inside a multi-line array, put this item
                // onto the *next* line & indent it

                $newYamlValue = "\n".$this->indentMultilineYamlArray($newYamlValue);
            } else {
                if ($firstItemInArray) {
                    // avoid the starting "," if first item in array
                    // but, DO add an ending ","
                    $newYamlValue .= ', ';
                } else {
                    $newYamlValue = ', '.$newYamlValue;
                }
            }
        }

        $newContents = substr($this->contents, 0, $this->currentPosition)
            .$newYamlValue
            .substr($this->contents, $this->currentPosition + $extraOffset);
        // manually bump the position: we didn't really move forward
        // any in the existing string, we just added our own new content
        $this->currentPosition += \strlen($newYamlValue);

        if (0 === $this->depth) {
            $newData = $this->currentData;
            $newData = $this->appendToArrayAtCurrentPath($key, $value, $newData);
        } else {
            // first, append to the "local" array: the little array we're currently working on
            $newLocalData = $this->getCurrentData(1);
            $newLocalData = $this->appendToArrayAtCurrentPath($key, $value, $newLocalData);
            // second, set this new array inside the full data
            $newData = $this->currentData;
            $newData = $this->setValueAtCurrentPath($newLocalData, $newData, 1);
        }

        $this->updateContents(
            $newContents,
            $newData,
            $this->currentPosition
        );
    }

    private function removeKeyFromYaml($key, $currentVal)
    {
        $endKeyPosition = $this->getEndOfKeyPosition($key);

        $endKeyPosition = $this->findEndPositionOfValue($currentVal, $endKeyPosition);

        if ($this->isCurrentArrayMultiline()) {
            $nextNewLine = $this->findNextLineBreak($endKeyPosition);
            // it's possible we're at the end of the file so there are no more \n
            if (false !== $nextNewLine) {
                $endKeyPosition = $nextNewLine;
            }
        } else {
            // find next ending character - , } or ]
            while (!\in_array($currentChar = substr($this->contents, $endKeyPosition, 1), [',', ']', '}'])) {
                ++$endKeyPosition;
            }

            // if a sequence or hash is ending, and the character before it is a space, keep that
            if ((']' === $currentChar || '}' === $currentChar) && ' ' === substr($this->contents, $endKeyPosition - 1, 1)) {
                --$endKeyPosition;
            }
        }

        $newPositionBump = 0;
        $extraContent = '';
        if (1 === \count($this->getCurrentData(1))) {
            // the key being removed is the *only* key
            // we need to close the new, empty array
            $extraContent = ' []';
            // when processing arrays normally, the position is set
            // after the opening character. Move this here manually
            $newPositionBump = 2;

            // if it *was* multiline, the indentation is now lost
            if ($this->isCurrentArrayMultiline()) {
                $this->indentationForDepths[$this->depth] = $this->indentationForDepths[$this->depth - 1];
            }
            // it is now definitely a sequence
            $this->arrayTypeForDepths[$this->depth] = self::ARRAY_TYPE_SEQUENCE;
            // it is now inline
            $this->arrayFormatForDepths[$this->depth] = self::ARRAY_FORMAT_INLINE;
        }

        $newContents = substr($this->contents, 0, $this->currentPosition)
            .$extraContent
            .substr($this->contents, $endKeyPosition);

        $newData = $this->currentData;
        $newData = $this->removeKeyAtCurrentPath($newData);

        // instead of passing the new +2 position below, we do it here
        // manually. This is because this it's not a real position move,
        // we manually (above) added some new chars that didn't exist before
        $this->currentPosition += $newPositionBump;

        $this->updateContents(
            $newContents,
            $newData,
            // position is unchanged: just some content was removed
            $this->currentPosition
        );
    }

    /**
     * Replaces the value at the current position with this value.
     *
     * The position should be set right at the start of this value
     * (i.e. after its key).
     *
     * @param mixed $value The new value to set into YAML
     */
    private function changeValueInYaml($value)
    {
        $originalVal = $this->getCurrentData();

        $endValuePosition = $this->findEndPositionOfValue($originalVal);

        $isMultilineValue = null !== $this->findPositionOfMultilineCharInLine($this->currentPosition);

        // In case of multiline, $value is converted as plain string like "Foo\nBar"
        // We need to keep it "as is"
        $newYamlValue = $isMultilineValue ? rtrim($value, "\n") : $this->convertToYaml($value);
        if ((!\is_array($originalVal) && \is_array($value))
            || ($this->isMultilineString($originalVal) && $this->isMultilineString($value))
        ) {
            // we're converting from a scalar to a (multiline) array
            // this means we need to break onto the next line

            // increase(override) the indentation
            $newYamlValue = "\n".$this->indentMultilineYamlArray($newYamlValue, $this->indentationForDepths[$this->depth] + $this->getPreferredIndentationSize());
        } elseif ($this->isCurrentArrayMultiline()
            && $this->isCurrentArraySequence()
            && !str_contains($this->getCurrentLine($this->currentPosition), ']')
        ) {
            // Modification
            // Fixed indentation issue of the new first element in multiline arrays like:
            // root:
            //     - 'value 1'
            //     - 'value 2'
            //     ...
            if ($this->isPositionAtBeginningOfArray()) {
                $newYamlValue = $this->indentMultilineYamlArray('    - ' . $newYamlValue);
            } else {
                // we are a multi-line sequence, so drop to next line, indent and add "- " in front
                $newYamlValue = "\n" . $this->indentMultilineYamlArray('- ' . $newYamlValue);
            }
        } else {
            // Ensure that we have a one-element inlined sequence array
            $currentLine = $this->getCurrentLine($this->currentPosition);
            if (str_contains($currentLine, ']')
                && substr_count($currentLine, '[') === substr_count($currentLine, ']')
            ) {
                $newYamlValue = '['.$newYamlValue;
            } else {
                // empty space between key & value
                $newYamlValue = ' '.$newYamlValue;
            }
        }

        $newPosition = $this->currentPosition + \strlen($newYamlValue);
        $isNextContentComment = $this->isPreviousLineComment($newPosition);
        if ($isNextContentComment) {
            ++$newPosition;
        }

        if ($isMultilineValue) {
            // strlen(" |")
            $newPosition -= 2;
        }

        $newContents = substr($this->contents, 0, $this->currentPosition)
            .($isMultilineValue ? ' |' : '')
            .$newYamlValue
            /*
             * If the next line is a comment, this means we probably had
             * a structure that looks like this:
             *     access_control:
             *         # - { path: ^/admin, roles: ROLE_ADMIN }
             *
             * In this odd case, we need to know that the next line
             * is a comment, so we can add an extra line break.
             * Otherwise, the result is something like:
             *     access_control:
             *         - { path: /foo, roles: ROLE_USER }        # - { path: ^/admin, roles: ROLE_ADMIN }
             */
            .($isNextContentComment ? "\n" : '')
            .substr($this->contents, $endValuePosition);

        $newData = $this->currentData;
        $newData = $this->setValueAtCurrentPath($value, $newData);

        $this->updateContents(
            $newContents,
            $newData,
            $newPosition
        );
    }

    private function advanceBeyondKey($key)
    {
        $this->log(sprintf('Advancing position beyond key "%s"', $key));
        $this->advanceCurrentPosition($this->getEndOfKeyPosition($key));
    }

    private function advanceBeyondEndOfPreviousKey($key)
    {
        $this->log('Advancing position beyond PREV key');
        $this->advanceCurrentPosition($this->getEndOfPreviousKeyPosition($key));
    }

    private function advanceBeyondMultilineArrayLastItem()
    {
        $this->log('Trying to advance beyond the last item in a multiline array');
        $this->advanceBeyondWhitespace();

        if ('#' === substr($this->contents, $this->currentPosition, 1)) {
            $this->log('The line ends with a comment, going to EOL');
            $this->advanceToEndOfLine();

            return;
        }

        $nextLineBreak = $this->findNextLineBreak($this->currentPosition);
        if ('}' === trim(substr($this->contents, $this->currentPosition, $nextLineBreak - $this->currentPosition))) {
            $this->log('The line ends with an array closing brace, going to EOL');
            $this->advanceToEndOfLine();
        }
    }

    private function advanceBeyondValue($value)
    {
        if (\is_array($value)) {
            throw new \LogicException('Do not pass an array to this method');
        }

        $this->log(sprintf('Advancing position beyond value "%s"', $value));
        $this->advanceCurrentPosition($this->findEndPositionOfValue($value));
    }

    private function getEndOfKeyPosition($key)
    {
        $matches = $this->getTheKeyMatch($key);

        if (empty($matches)) {
            // for integers, the key may not be explicitly printed
            if (\is_int($key)) {
                return $this->currentPosition;
            }

            throw new YamlManipulationFailedException(sprintf('Cannot find the key "%s"', $key));
        }

        return $matches[0][1] + \strlen($matches[0][0]);
    }

    /**
     * Finds the end position of the key that comes *before* this key.
     */
    private function getEndOfPreviousKeyPosition($key): int
    {
        $matches = $this->getTheKeyMatch($key);

        if (empty($matches)) {
            // for integers, the key may not be explicitly printed
            if (\is_int($key)) {
                return $this->currentPosition;
            }

            $cursor = $this->currentPosition;

            while ('-' !== substr($this->contents, $cursor - 1, 1) && -1 !== $cursor) {
                --$cursor;
            }

            if ($cursor >= 0) {
                return $cursor;
            }

            throw new YamlManipulationFailedException(sprintf('Cannot find the previous key "%s"', $key));
        }

        $startOfKey = $matches[0][1];

        // if we're at the start of the file, we're done!
        if (0 === $startOfKey) {
            return 0;
        }

        /*
         * Now, walk backwards: so that the position is before any
         * whitespace, commas or line breaks. Basically, we want to go
         * back to the first character *after* the previous key started.
         */
        // walk back any spaces
        while (' ' === substr($this->contents, $startOfKey - 1, 1)) {
            --$startOfKey;
        }

        // find either a line break or a , that is the end of the previous key
        while (\in_array($char = substr($this->contents, $startOfKey - 1, 1), [',', "\n"])) {
            --$startOfKey;
        }

        // look for \r\n
        if ("\r" === substr($this->contents, $startOfKey - 1, 1)) {
            --$startOfKey;
        }

        // if we're at the start of a line, if the prev line is a comment, move before it
        if ($this->isCharLineBreak(substr($this->contents, $startOfKey, 1))) {
            // move one (or two) forward so the code below finds the *previous* line
            ++$startOfKey;

            if ($this->isCharLineBreak(substr($this->contents, $startOfKey, 1))) {
                ++$startOfKey;
            }

            /*
             * In a multi-line array, the previous line(s) could be 100% comments.
             * In that situation, we want to rewind to *before* the comments, so
             * that those comments are attached to the current key and move with it.
             */
            while ($this->isPreviousLineComment($startOfKey)) {
                --$startOfKey;
                // if this is a \n\r, we need to go back an extra char
                if ("\r" === substr($this->contents, $startOfKey - 1, 1)) {
                    --$startOfKey;
                }

                while (!$this->isCharLineBreak(substr($this->contents, $startOfKey - 1, 1))) {
                    --$startOfKey;

                    // we've reached the start of the file!
                    if (0 === $startOfKey) {
                        break;
                    }
                }
            }

            if (0 !== $startOfKey) {
                // move backwards one onto the previous line
                --$startOfKey;
            }

            // look for \n\r situation
            if ("\r" === substr($this->contents, $startOfKey - 1, 1)) {
                --$startOfKey;
            }
        }

        return $startOfKey;
    }

    private function getTheKeyMatch(mixed $key): ?array
    {
        // Try to find key match
        preg_match(
            $this->getKeyRegex($key),
            $this->contents,
            $matches,
            \PREG_OFFSET_CAPTURE,
            $this->currentPosition
        );

        // The key is not detected with the regex but such keys are present in the yaml source
        // Try to find it in other way
        if (empty($matches) && !empty($key) && str_contains($this->contents, $key)) {
            $startOfKey = strpos($this->contents, $key, $this->currentPosition) ?: strpos($this->contents, $key);

            $matches[0][0] = $key;
            $matches[0][1] = $startOfKey;
        }

        // Edge case: Integer-like or integer-containing keys. E.g. "123":, 'Case 2':
        // Ensure that the correct key was found and exclude wrong key detection
        if (is_int($key)
            && !empty($matches)
            && $matches[0][1] - $this->currentPosition !== strlen($key)
        ) {
            $matches = null;
        }

        return $matches;
    }

    private function getKeyRegex($key)
    {
        return sprintf('#(?<!\w)\$?%s\'?( )*:#', preg_quote($key));
    }

    private function updateContents(string $newContents, array $newData, int $newPosition)
    {
        $this->log('updateContents()');

        // validate the data
        try {
            $parsedContentsData = $this->parse($newContents);

            // normalize indexes on sequences to avoid comparison problems
            $parsedContentsData = $this->normalizeSequences($parsedContentsData);
            $newData = $this->normalizeSequences($newData);
            if ($parsedContentsData !== $newData) {
                // Modification
                // Ensure that the arrays has different keys
                if (!$this->compareArrayKeys($parsedContentsData, $newData)) {
                    throw new YamlManipulationFailedException(
                        sprintf(
                            "Could not update YAML automatically\nSuggested changes:\n%s",
                            $this->differ->diff($this->contents, $newContents),
                        )
                    );
                }
            }
        } catch (ParseException $e) {

            throw new YamlManipulationFailedException(
                sprintf(
                    "Could not update YAML automatically\nSuggested changes:\n%s",
                    $this->differ->diff($this->contents, $newContents),
                )
            );
        }

        // must be called before changing the contents
        $this->advanceCurrentPosition($newPosition);
        $this->contents = $newContents;
        $this->currentData = $newData;
    }

    private function convertToYaml($data): string
    {
        $indent = $this->depth > 0 && isset($this->indentationForDepths[$this->depth])
            ? intdiv($this->indentationForDepths[$this->depth], $this->depth)
            : 4;

        // Modification
        // The indentation must be greater than zero on depth 1 and more
        if ($indent < 1 && $this->depth > 0) {
            $indent = intdiv($this->indentationForDepths[$this->depth - 1], $this->depth - 1);
            $this->indentationForDepths[$this->depth] = $this->indentationForDepths[$this->depth - 1] + 4;
        }

        $newDataString = Yaml::dump($data, 4, $indent);
        // new line is appended: remove it
        $newDataString = rtrim($newDataString, "\n");

        return $newDataString;
    }

    /**
     * Adds a new item (with the given key) to the $data array at the correct position.
     *
     * The $data should be the simple array that should be updated and that
     * the current path is pointing to. The current path is used
     * to determine *where* in the array to put the new item (so that it's
     * placed in the middle when necessary).
     */
    private function appendToArrayAtCurrentPath($key, $value, array $data): array
    {
        if ($this->isPositionAtBeginningOfArray()) {
            // this should be prepended
            return [$key => $value] + $data;
        }

        $offset = array_search($this->previousPath[$this->depth], array_keys($data));

        // if the target is currently the end of the array, just append
        if ($offset === (\count($data) - 1)) {
            $data[$key] = $value;

            return $data;
        }

        return array_merge(
            \array_slice($data, 0, $offset + 1),
            [$key => $value],
            \array_slice($data, $offset + 1, null)
        );
    }

    private function setValueAtCurrentPath($value, array $data, int $limitLevels = 0)
    {
        // create a reference
        $dataRef = &$data;

        // start depth at $limitLevels (instead of 0) to properly detect when to set the key
        $depth = $limitLevels;
        $path = \array_slice($this->currentPath, 0, \count($this->currentPath) - $limitLevels);
        foreach ($path as $key) {
            if (!\array_key_exists($key, $dataRef)) {
                throw new \LogicException(sprintf('Could not find the key "%s" from the current path "%s" in data "%s"', $key, implode(', ', $path), var_export($data, true)));
            }

            if ($depth === $this->depth) {
                // we're at the correct depth!
                if (self::UNSET_KEY_FLAG === $value) {
                    unset($dataRef[$key]);

                    // if this is a sequence, reindex the keys
                    if ($this->isCurrentArraySequence()) {
                        $dataRef = array_values($dataRef);
                    }
                } else {
                    $dataRef[$key] = $value;
                }

                return $data;
            }

            // get a deeper reference
            $dataRef = &$dataRef[$key];

            ++$depth;
        }

        throw new \LogicException('The value was not updated.');
    }

    private function removeKeyAtCurrentPath(array $data): array
    {
        return $this->setValueAtCurrentPath(self::UNSET_KEY_FLAG, $data);
    }

    /**
     * Returns the value in the current data that is currently
     * being looked at.
     *
     * This could fail if the currentPath is for new data.
     *
     * @param int $limitLevels If set to 1, the data 1 level up will be returned
     */
    private function getCurrentData(int $limitLevels = 0)
    {
        $data = $this->currentData;
        $path = \array_slice($this->currentPath, 0, \count($this->currentPath) - $limitLevels);
        foreach ($path as $key) {
            if (!\array_key_exists($key, $data)) {
                throw new \LogicException(sprintf('Could not find the key "%s" from the current path "%s" in data "%s"', $key, implode(', ', $path), var_export($this->currentData, true)));
            }

            $data = $data[$key];
        }

        return $data;
    }

    private function findEndPositionOfValue($value, $offset = null)
    {
        if (\is_array($value)) {
            $currentPosition = $this->currentPosition;
            $this->log('Walking across array to find end position of array');
            $this->updateData($value);
            $endKeyPosition = $this->currentPosition;
            $this->currentPosition = $currentPosition;

            return $endKeyPosition;
        }

        if (\is_scalar($value) || null === $value) {
            $offset = null === $offset ? $this->currentPosition : $offset;

            if (\is_bool($value)) {
                // (?i) & (?-i) opens/closes case insensitive match
                $pattern = sprintf('(?i)%s(?-i)', $value ? 'true' : 'false');
            } elseif (null === $value) {
                $pattern = '(~|NULL|null|\n)';
            } else {
                // Multiline value ends with \n.
                // If we remove this character, the next property will ne merged with this value
                $quotedValue = preg_quote(rtrim($value, "\n"), '#');
                $patternValue = $quotedValue;

                // Iterates until we find a new line char or we reach end of file
                if (null !== $this->findPositionOfMultilineCharInLine($offset)) {
                    $patternValue = str_replace(["\r\n", "\n"], '\r?\n\s*', $quotedValue);
                }

                $pattern = sprintf('\'?"?%s\'?"?', $patternValue);
            }

            // a value like "foo:" can simply end a file
            // this means the value is null
            if ($offset === \strlen($this->contents)) {
                return $offset;
            }

            preg_match(
                sprintf('#%s#', $pattern),
                $this->contents,
                $matches,
                \PREG_OFFSET_CAPTURE,
                $offset
            );

            // If the value was not found probably it was changed while parsing
            // Some chars probably were escaped/unescaped
            // Try to find the value by the parts
            if (empty($matches)) {
                // Get the parts of the value that contains strings and digits
                preg_match_all('/\w+/', $value, $matches);

                if ($this->isMultilineString($value)) {
                    $patternParts = array_map(function ($part) {
                        return preg_quote($part, '/');
                    }, $matches[0]);

                    $pattern = '/'. implode('\s*.*?\s*', $patternParts) .'/s';
                } else {
                    $pattern = '/\s[^a-zA-Z0-9]*' . implode('[^a-zA-Z0-9]*', $matches[0]) . '[^a-zA-Z0-9]*\n/';
                }

                preg_match($pattern, $this->contents, $match, \PREG_OFFSET_CAPTURE, $offset);

                // Re-check if the value is present by offset-reducing
                if (empty($match)
                    && $this->getValueByPath($this->currentData, $this->currentPath)
                    && $value = $this->getValueByPath($this->currentData, $this->currentPath)
                ) {
                    $tmpOffset = $offset;
                    while(empty($match)) {
                        $tmpOffset -= strlen($value);
                        if ($tmpOffset > 0) {
                            preg_match($pattern, $this->contents, $match, \PREG_OFFSET_CAPTURE, $tmpOffset);
                        } else {
                            break;
                        }
                    }

                    if (!empty($match)) {
                        $position = (int)$match[0][1] + strlen($match[0][0]);
                        $offset = $position;
                    }
                } else {
                    $value = $match[0][0];
                    // We have to start from the previous line-break
                    $position = (int)$match[0][1] + strlen($match[0][0]) - 1;
                }

                if (!str_contains(substr($this->contents, $offset), $value)) {
                    throw new YamlManipulationFailedException(sprintf('Cannot find the original value "%s"', $value));
                }
            } else {
                $position = (int)$matches[0][1] + strlen($matches[0][0]);
            }

            // edge case where there is a comment between the current position
            // and the value we're looking for AND that comment contains an
            // exact string match for the value we're looking for
            if ($this->isFinalLineComment(substr($this->contents, $this->currentPosition, $position - $this->currentPosition))) {
                return $this->findEndPositionOfValue($value, $position);
            }

            if (null === $value && "\n" === $matches[0][0] && !$this->isCurrentLineComment($position)) {
                $this->log('Zero-length null value, next line not a comment, take a step back');
                --$position;
            }

            return $position;
        }

        // there are other possible values, but we don't support them
        throw new YamlManipulationFailedException(sprintf('Unsupported Yaml value of type "%s"', \gettype($value)));
    }

    private function advanceCurrentPosition(int $newPosition)
    {
        $this->log(sprintf('advanceCurrentPosition() from %d to %d', $this->currentPosition, $newPosition), true);
        $originalPosition = $this->currentPosition;
        $this->currentPosition = $newPosition;

        // if we're not changing, or moving backwards, don't count indent
        // changes
        if ($newPosition <= $originalPosition) {
            return;
        }

        /*
         * A bit of a workaround. At times, this function will be called when the
         * position is at the beginning of the line: so, one character *after*
         * a line break. In that case, if there are a group of spaces at the
         * beginning of this first line, they *should* be used to calculate the new
         * indentation. To force this, if we detect this situation, we move one
         * character backwards, so that the first line is considered a valid line
         * to look for indentation.
         */
        if ($this->isCharLineBreak(substr($this->contents, $originalPosition - 1, 1))) {
            --$originalPosition;
        }

        // look for empty lines and track the current indentation
        $advancedContent = substr($this->contents, $originalPosition, $newPosition - $originalPosition);
        $previousIndentation = $this->indentationForDepths[$this->depth];
        $newIndentation = $previousIndentation;

        if ("\n" === $advancedContent) {
            $this->log('Just a linebreak, no indent changes');

            return;
        }

        if (str_contains($advancedContent, "\n")) {
            $lines = explode("\n", $advancedContent);
            if (!empty($lines)) {
                $lastLine = $lines[\count($lines) - 1];
                $lastLine = trim($lastLine, "\r");
                $indentation = 0;
                while (' ' === substr($lastLine, $indentation, 1)) {
                    ++$indentation;
                }

                $newIndentation = $indentation;
            }
        }

        $this->log(sprintf('Calculating new indentation: changing from %d to %d', $this->indentationForDepths[$this->depth], $newIndentation), true);
        $this->indentationForDepths[$this->depth] = $newIndentation;
    }

    private function decrementDepth()
    {
        $this->log('Moving up 1 level of depth');
        unset($this->indentationForDepths[$this->depth]);
        unset($this->arrayFormatForDepths[$this->depth]);
        unset($this->arrayTypeForDepths[$this->depth]);
        unset($this->currentPath[$this->depth]);
        unset($this->previousPath[$this->depth]);
        --$this->depth;
    }

    private function getCurrentIndentation(int $override = null): string
    {
        $indent = $override ?? $this->indentationForDepths[$this->depth];

        return str_repeat(' ', $indent);
    }

    private function log(string $message, $includeContent = false)
    {
        if (null === $this->logger) {
            return;
        }

        $context = [
            'key' => $this->currentPath[$this->depth] ?? 'n/a',
            'depth' => $this->depth,
            'position' => $this->currentPosition,
            'indentation' => $this->indentationForDepths[$this->depth],
            'type' => $this->arrayTypeForDepths[$this->depth],
            'format' => $this->arrayFormatForDepths[$this->depth],
        ];

        if ($includeContent) {
            $context['content'] = sprintf(
                '>%s<',
                str_replace(["\r\n", "\n"], ['\r\n', '\n'], substr($this->contents, $this->currentPosition, 50))
            );
        }

        $this->logger->debug($message, $context);
    }

    private function isCurrentArrayMultiline(): bool
    {
        return self::ARRAY_FORMAT_MULTILINE === $this->arrayFormatForDepths[$this->depth];
    }

    private function isCurrentArraySequence(): bool
    {
        return self::ARRAY_TYPE_SEQUENCE === $this->arrayTypeForDepths[$this->depth];
    }

    /**
     * Attempts to guess if the array at the current position
     * is a multi-line array or an inline array.
     */
    private function guessNextArrayTypeAndAdvance(): string
    {
        while (true) {
            if ($this->isEOF()) {
                throw new \LogicException('Could not determine array type');
            }

            // get the next char & advance immediately
            $nextCharacter = substr($this->contents, $this->currentPosition, 1);
            // advance, but without advanceCurrentPosition()
            // because we are either moving along one line until [ {
            // or we are finding a line break and stopping: indentation
            // should not be calculated
            ++$this->currentPosition;

            // Modification
            // Added handling of the keyed arrays as values of the sequenced array
            // E.g.
            //    ...
            // actions:
            //     - '@call_service_method':
            //         service: service_name.service_method
            //    ...
            if ($this->isCharLineBreak($nextCharacter) || " " === $nextCharacter) {
                return self::ARRAY_FORMAT_MULTILINE;
            }

            if ('[' === $nextCharacter || '{' === $nextCharacter) {
                return self::ARRAY_FORMAT_INLINE;
            }
        }
    }

    /**
     * Advance until you find *one* of the characters in $chars.
     */
    private function findPositionOfNextCharacter(array $chars)
    {
        $currentPosition = $this->currentPosition;
        while (true) {
            if ($this->isEOF($currentPosition)) {
                throw new \LogicException(sprintf('Could not find any characters: %s', implode(', ', $chars)));
            }

            // get the next char & advance immediately
            $nextCharacter = substr($this->contents, $currentPosition, 1);
            ++$currentPosition;

            if (\in_array($nextCharacter, $chars)) {
                return $currentPosition;
            }
        }
    }

    private function advanceBeyondWhitespace()
    {
        while (' ' === substr($this->contents, $this->currentPosition, 1)) {
            if ($this->isEOF()) {
                return;
            }

            ++$this->currentPosition;
        }
    }

    private function advanceToEndOfLine()
    {
        $newPosition = $this->currentPosition;
        while (!$this->isCharLineBreak(substr($this->contents, $newPosition, 1))) {
            if ($this->isEOF($newPosition)) {
                // found the end of the file!
                break;
            }

            ++$newPosition;
        }

        $this->advanceCurrentPosition($newPosition);
    }

    /**
     * Duplicated from Symfony's Inline::isHash().
     *
     * Returns true if the value must be rendered as a hash,
     * which includes an indexed array, if the indexes are
     * not sequential.
     */
    private function isHash($value): bool
    {
        if ($value instanceof \stdClass || $value instanceof \ArrayObject) {
            return true;
        }
        $expectedKey = 0;
        foreach ($value as $key => $val) {
            if ($key !== $expectedKey++) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSequences(array $data)
    {
        // https://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential/4254008#4254008
        $hasStringKeys = function (array $array) {
            return \count(array_filter(array_keys($array), 'is_string')) > 0;
        };

        foreach ($data as $key => $val) {
            if (!\is_array($val)) {
                continue;
            }

            if (!$hasStringKeys($val)) {
                // avoid indexed arrays with non-sequential keys
                // e.g. if a key was removed. This causes comparison issues
                $val = array_values($val);
                $data[$key] = $val;
            }

            $data[$key] = $this->normalizeSequences($val);
        }

        return $data;
    }

    private function removeMetadataKeys(array $data)
    {
        foreach ($data as $key => $val) {
            if (\is_array($val)) {
                $data[$key] = $this->removeMetadataKeys($val);

                continue;
            }

            if (self::EMPTY_LINE_PLACEHOLDER_VALUE === $val) {
                unset($data[$key]);
            }

            if (null !== $val && str_starts_with($val, self::COMMENT_PLACEHOLDER_VALUE)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    private function replaceSpecialMetadataCharacters()
    {
        while (preg_match('#\n.*'.self::EMPTY_LINE_PLACEHOLDER_VALUE.'.*\n#', $this->contents, $matches)) {
            $this->contents = str_replace($matches[0], "\n\n", $this->contents);
        }

        while (preg_match('#\n(\s*).*\''.self::COMMENT_PLACEHOLDER_VALUE.'(.*)\'#', $this->contents, $matches)) {
            $fullMatch = $matches[0];
            $indentation = $matches[1];
            $comment = $matches[2];

            $this->contents = str_replace(
                $fullMatch,
                sprintf("\n%s#%s", $indentation, $comment),
                $this->contents
            );
        }
    }

    /**
     * Try to guess a preferred indentation level.
     */
    private function getPreferredIndentationSize(): int
    {
        return isset($this->indentationForDepths[1]) && $this->indentationForDepths[1] > 0 ? $this->indentationForDepths[1] : 4;
    }

    /**
     * For the array currently being processed, are we currently
     * handling the first key inside of it?
     */
    private function isPositionAtBeginningOfArray(): bool
    {
        return null === $this->previousPath[$this->depth];
    }

    private function manuallyIncrementIndentation()
    {
        $this->indentationForDepths[$this->depth] += $this->getPreferredIndentationSize();
    }

    private function isEOF(int $position = null)
    {
        $position = null === $position ? $this->currentPosition : $position;

        return $position === \strlen($this->contents);
    }

    private function isPreviousLineComment(int $position): bool
    {
        $line = $this->getPreviousLine($position);

        if (null === $line) {
            return false;
        }

        return $this->isLineComment($line);
    }

    private function isCurrentLineComment(int $position): bool
    {
        $line = $this->getCurrentLine($position);

        if (null === $line) {
            return false;
        }

        return $this->isLineComment($line);
    }

    private function isLineComment(string $line): bool
    {
        // adopted from Parser::isCurrentLineComment() from symfony/yaml
        $ltrimmedLine = ltrim($line, ' ');

        return '' !== $ltrimmedLine && '#' === $ltrimmedLine[0];
    }

    private function isFinalLineComment(string $content): bool
    {
        if (!$content) {
            return false;
        }

        $content = str_replace("\r", "\n", $content);

        $lines = explode("\n", $content);
        $line = end($lines);

        return $this->isLineComment($line);
    }

    private function getPreviousLine(int $position)
    {
        // find the previous \n by finding the last one in the content up to the position
        $endPos = strrpos(substr($this->contents, 0, $position), "\n");
        if (false === $endPos) {
            // there is no previous line
            return null;
        }

        $startPos = strrpos(substr($this->contents, 0, $endPos), "\n");
        if (false === $startPos) {
            // we're at the beginning of the file
            $startPos = 0;
        } else {
            // move 1 past the line break
            ++$startPos;
        }

        $previousLine = substr($this->contents, $startPos, $endPos - $startPos);

        return trim($previousLine, "\r");
    }

    private function getCurrentLine(int $position)
    {
        $startPos = strrpos(substr($this->contents, 0, $position), "\n") + 1;
        $endPos = strpos($this->contents, "\n", $startPos);

        $this->log(sprintf('Looking for current line from %d to %d', $startPos, $endPos));

        $line = substr($this->contents, $startPos, $endPos - $startPos);

        return trim($line, "\r");
    }

    private function findNextLineBreak(int $position)
    {
        $nextNPos = strpos($this->contents, "\n", $position);
        $nextRPos = strpos($this->contents, "\r", $position);

        if (false === $nextNPos) {
            return false;
        }

        if (false === $nextRPos) {
            return $nextNPos;
        }

        // find the first possible line break character
        $nextLinePos = min($nextNPos, $nextRPos);

        // check for a \r\n situation
        if (($nextLinePos + 1) === $nextNPos) {
            ++$nextLinePos;
        }

        return $nextLinePos;
    }

    private function isCharLineBreak(string $char): bool
    {
        return "\n" === $char || "\r" === $char;
    }

    /**
     * Takes an unindented multi-line YAML string and indents it so
     * it can be inserted into the current position.
     *
     * Usually an empty line needs to be prepended to this result before
     * adding to the content.
     */
    private function indentMultilineYamlArray(string $yaml, int $indentOverride = null): string
    {
        // Fix the indent for the last element in sequenced array
        if ($this->depth > 1 && $this->indentationForDepths[$this->depth] < $this->indentationForDepths[$this->depth - 1]) {
            $path = $this->currentPath;
            // If the key is int - we might be in the sequenced array
            if (is_int(end($path))) {
                $path[] = array_pop($path) + 1;
                // Null will be returned because we don`t have the next element
                if (!$this->getValueByPath($this->currentData, $path)) {
                    $this->indentationForDepths[$this->depth] = $this->indentationForDepths[$this->depth - 1] + 4;
                }
            }
        }

        $indent = $this->getCurrentIndentation($indentOverride);

        // But, if the *value* is an array, then ITS children will
        // also need to be indented artificially by the same amount
        $yaml = str_replace("\n", "\n".$indent, $yaml);

        if ($this->isMultilineString($yaml)) {
            // Remove extra indentation in case of blank line in multiline string
            $yaml = str_replace("\n".$indent."\n", "\n\n", $yaml);
        }

        // now indent this level
        return $indent.$yaml;
    }

    private function findPositionOfMultilineCharInLine(int $position): ?int
    {
        $cursor = $position;
        while (!$this->isCharLineBreak($currentChar = substr($this->contents, $cursor + 1, 1)) && !$this->isEOF($cursor)) {
            if ('|' === $currentChar) {
                return $cursor;
            }

            ++$cursor;
        }

        return null;
    }

    private function isMultilineString($value): bool
    {
        return \is_string($value) && str_contains($value, "\n");
    }
    // @codingStandardsIgnoreEnd

    private function parse(string $input, int $flags = 0): mixed
    {
        $input = $this->preProcessInput($input);

        $flags = 0 === $flags ? Yaml::PARSE_CUSTOM_TAGS : $flags;
        try {
            $data = Yaml::parse($input, $flags);
        } catch (ParseException $e) {
            $input = $this->isContentsPreProcessed ? $this->preProcessInput($input) : $this->postProcessInput($input);
        } finally {
            $data = Yaml::parse($input, $flags);
        }

        if (is_array($data)) {
            $this->preProcessTaggedValues($data);
        }

        return $data;
    }

    private function preProcessInput(string $input): string
    {
        $result = $input;

        $patternsAndReplacementsList = [
            '/(!php\/const\s+[^\s]*)(?=:\s|:\n)/' => "'$1'",
            '/!!binary\s+[^\n]*/' => "'$0'"
        ];

        foreach ($patternsAndReplacementsList as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $result) ?? $result;
        }

        $this->isContentsPreProcessed = ($input !== $result);

        return $result;
    }

    private function postProcessInput(string $input): string
    {
        if (!$this->isContentsPreProcessed) {
            return $input;
        }

        $this->isContentsPreProcessed = false;
        $result = $input;

        $patternsAndReplacementsList = [
            "/'(!php\/const\s+[^\s]*)(?=:\s|:\n)'/" => '$1',
            "/'(!php\/const\s+[^\s]*)'/" => '$1',
            "/'!!binary\s+[^\n]*'/" => '$1',
        ];

        foreach ($patternsAndReplacementsList as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $result) ?? $result;
        }

        return $result;
    }

    private function preProcessTaggedValues(array &$array): void
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->preProcessTaggedValues($value);
            } elseif ($value instanceof TaggedValue) {
                $value = Yaml::dump(input: $value, flags: Yaml::DUMP_OBJECT);
                $value = rtrim($value, PHP_EOL);

                // The value should be already present, but probably some quotes or etc. were missed
                if (!str_contains($this->contents, $value)) {
                    // Get string parts of the value
                    preg_match_all('/\w+/', $value, $matches);

                    $escapedLastChar = preg_quote(substr($value, -1), '/') . '/';
                    $pattern = '/\![^\w]*' . implode('[^\w]*', $matches[0]) . '[^\w]*' . $escapedLastChar;

                    preg_match($pattern, $this->contents, $match);

                    $value = $match[0] ?? $value;
                }
            }
        }
    }

    private function getValueByPath(array $data, array $path): mixed
    {
        foreach ($path as $key) {
            if (!isset($data[$key])) {
                return null;
            }
            $data = $data[$key];
        }
        return $data;
    }

    private function compareArrayKeys(array $firstArray, array $secondArray): bool
    {
        $firstArrayKeys = array_keys($firstArray);
        $secondArrayKeys2 = array_keys($secondArray);

        if (count(array_diff($firstArrayKeys, $secondArrayKeys2)) > 0
            || count(array_diff($secondArrayKeys2, $firstArrayKeys)) > 0
        ) {
            return false;
        }

        foreach ($firstArray as $key => $value) {
            if (is_array($value) && is_array($secondArray[$key])) {
                if (!$this->compareArrayKeys($value, $secondArray[$key])) {
                    return false;
                }
            } elseif (is_array($value) || is_array($secondArray[$key])) {
                return false;
            }
        }

        return true;
    }
}
