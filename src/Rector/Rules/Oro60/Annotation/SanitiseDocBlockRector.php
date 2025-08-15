<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use RectorPrefix202507\Webmozart\Assert\Assert;

/**
 * Sanitizes PHPDoc blocks by replacing specified characters or strings in comment lines,
 * leaving annotation lines unchanged.
 */
class SanitiseDocBlockRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $replaceMap = [];

    public function __construct(
        private readonly PhpDocInfoFactory $phpDocInfoFactory,
    ) {
    }

    #[\Override]
    public function configure(array $configuration): void
    {
        Assert::allString($configuration);
        $this->replaceMap = $configuration;
    }

    #[\Override]
    public function getNodeTypes(): array
    {
        return [Class_::class, Property::class, ClassMethod::class];
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        // Use a cloned object to avoid caching parsing result of original node
        $clonedNode = clone $node;
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($clonedNode);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }

        $docComment = $node->getDocComment();
        if (!$docComment instanceof Doc) {
            return null;
        }

        $tagsCount = $this->countTagsInDocComment($docComment);
        if (0 !== $tagsCount && (count($phpDocInfo->getPhpDocNode()->children) <= $tagsCount)) {
            $sanitizedText = $this->sanitizeDocComment($docComment->getText());
            $node->setDocComment(new Doc($sanitizedText));

            return $node;
        }

        return null;
    }

    /**
     * Count the number of annotation tags in a DocComment.
     */
    private function countTagsInDocComment(Doc $docComment): int
    {
        $comment = $docComment->getText();

        return substr_count($comment, '* @') + substr_count($comment, '*@');
    }

    /**
     * Sanitize the DocComment text by applying replacements to non-annotation lines.
     */
    private function sanitizeDocComment(string $textValue): string
    {
        $lines = explode("\n", $textValue);
        $processedLines = [];
        $inAnnotationSection = false;

        foreach ($lines as $line) {
            // Check if this line contains an annotation (starts with @ after trimming * and whitespace)
            $trimmedLine = trim($line, " \t\r\n*/");

            if (str_starts_with($trimmedLine, '@')) {
                $inAnnotationSection = true;
            }

            if ($inAnnotationSection) {
                // Keep annotation lines unchanged
                $processedLines[] = $line;
            } else {
                // Process comment lines - replace characters according to configuration
                $processedLines[] = $this->replaceCharactersInCommentLine($line);
            }
        }

        return implode("\n", $processedLines);
    }

    /**
     * Replace configured characters in a comment line while preserving structural elements.
     */
    private function replaceCharactersInCommentLine(string $line): string
    {
        // Don't modify structural lines like /** and */
        $trimmedLine = trim($line);
        if ($trimmedLine === '/**' || $trimmedLine === '*/') {
            return $line;
        }

        // Replace mapped characters in the comment content
        foreach ($this->replaceMap as $search => $replace) {
            $line = str_replace($search, $replace, $line);
        }

        return $line;
    }
}
