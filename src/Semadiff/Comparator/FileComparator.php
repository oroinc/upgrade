<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Comparator;

use Oro\UpgradeToolkit\Semadiff\Extractor\ClassInfo;
use Oro\UpgradeToolkit\Semadiff\Extractor\ClassInfoExtractor;
use Oro\UpgradeToolkit\Semadiff\Extractor\ConstantInfo;
use Oro\UpgradeToolkit\Semadiff\Extractor\MethodInfo;
use Oro\UpgradeToolkit\Semadiff\Extractor\PropertyInfo;

final class FileComparator
{
    private ClassInfoExtractor $extractor;

    public function __construct()
    {
        $this->extractor = new ClassInfoExtractor();
    }

    public function compare(string $beforeCode, string $afterCode): FileComparisonResult
    {
        return $this->doCompare($beforeCode, $afterCode, null);
    }

    /**
     * Compare before/after code, reusing a pre-parsed after AST to avoid redundant parsing.
     *
     * @param \PhpParser\Node\Stmt[] $afterAst Raw parser output (before BodyNormalizer)
     */
    public function compareWithParsedAfter(string $beforeCode, string $afterCode, array $afterAst): FileComparisonResult
    {
        return $this->doCompare($beforeCode, $afterCode, $afterAst);
    }

    /**
     * @param \PhpParser\Node\Stmt[]|null $preParsedAfterAst
     */
    private function doCompare(string $beforeCode, string $afterCode, ?array $preParsedAfterAst): FileComparisonResult
    {
        $allParseErrors = [];

        try {
            [$beforeClasses, $beforeParseErrors] = $this->extractor->extractWithErrors($beforeCode);
            foreach ($beforeParseErrors as $err) {
                $allParseErrors[] = 'before: ' . $err;
            }
        } catch (\Throwable $e) {
            return new FileComparisonResult(
                classStructureChanged: false,
                signatureChanged: false,
                bodyChanged: true,
                membersAddedOrRemoved: false,
                details: ['Failed to parse before file: ' . $e->getMessage()],
                parseErrors: ['before: ' . $e->getMessage()],
            );
        }

        try {
            if ($preParsedAfterAst !== null) {
                [$afterClasses, $afterParseErrors] = $this->extractor->extractWithErrorsFromAst($preParsedAfterAst);
            } else {
                [$afterClasses, $afterParseErrors] = $this->extractor->extractWithErrors($afterCode);
            }
            foreach ($afterParseErrors as $err) {
                $allParseErrors[] = 'after: ' . $err;
            }
        } catch (\Throwable $e) {
            return new FileComparisonResult(
                classStructureChanged: false,
                signatureChanged: false,
                bodyChanged: true,
                membersAddedOrRemoved: false,
                details: ['Failed to parse after file: ' . $e->getMessage()],
                parseErrors: ['after: ' . $e->getMessage()],
            );
        }

        $classStructureChanged = false;
        $signatureChanged = false;
        $bodyChanged = false;
        $membersAddedOrRemoved = false;
        $details = [];

        // Index classes by name
        $beforeByName = [];
        foreach ($beforeClasses as $class) {
            $beforeByName[$class->name] = $class;
        }

        $afterByName = [];
        foreach ($afterClasses as $class) {
            $afterByName[$class->name] = $class;
        }

        // Check for added/removed classes
        $allNames = array_unique(array_merge(array_keys($beforeByName), array_keys($afterByName)));

        foreach ($allNames as $name) {
            $before = $beforeByName[$name] ?? null;
            $after = $afterByName[$name] ?? null;

            if ($before === null) {
                $membersAddedOrRemoved = true;
                $details[] = "Class added: $name";
                continue;
            }

            if ($after === null) {
                $membersAddedOrRemoved = true;
                $details[] = "Class removed: $name";
                continue;
            }

            // Class made final
            if (!$before->isFinal && $after->isFinal) {
                $signatureChanged = true;
                $details[] = "Class made final: $name";
            }

            // Compare class structure
            if (!$before->structureEquals($after)) {
                $classStructureChanged = true;
                $details[] = "Class structure changed: $name";
            }

            // Compare methods
            $this->compareMethods($before, $after, $signatureChanged, $bodyChanged, $membersAddedOrRemoved, $details);

            // Compare properties
            $this->compareProperties($before, $after, $signatureChanged, $bodyChanged, $membersAddedOrRemoved, $details);

            // Compare constants
            $this->compareConstants($before, $after, $signatureChanged, $bodyChanged, $membersAddedOrRemoved, $details);
        }

        return new FileComparisonResult(
            classStructureChanged: $classStructureChanged,
            signatureChanged: $signatureChanged,
            bodyChanged: $bodyChanged,
            membersAddedOrRemoved: $membersAddedOrRemoved,
            details: $details,
            parseErrors: $allParseErrors,
        );
    }

    /**
     * @param string[] $details
     */
    private function compareMethods(
        ClassInfo $before,
        ClassInfo $after,
        bool &$signatureChanged,
        bool &$bodyChanged,
        bool &$membersAddedOrRemoved,
        array &$details,
    ): void {
        $beforeMethods = [];
        foreach ($before->methods as $m) {
            $beforeMethods[$m->name] = $m;
        }

        $afterMethods = [];
        foreach ($after->methods as $m) {
            $afterMethods[$m->name] = $m;
        }

        $allMethodNames = array_unique(array_merge(array_keys($beforeMethods), array_keys($afterMethods)));

        foreach ($allMethodNames as $name) {
            $bMethod = $beforeMethods[$name] ?? null;
            $aMethod = $afterMethods[$name] ?? null;

            if ($bMethod === null) {
                $membersAddedOrRemoved = true;
                $details[] = "Method added: {$before->name}::$name";
                continue;
            }

            if ($aMethod === null) {
                $membersAddedOrRemoved = true;
                $details[] = "Method removed: {$before->name}::$name";
                continue;
            }

            if (!$bMethod->signatureEquals($aMethod)) {
                $signatureChanged = true;
                $this->emitMethodSignatureDetails($before->name, $name, $bMethod, $aMethod, $details);
            }

            if (!$bMethod->bodyEquals($aMethod)) {
                $bodyChanged = true;
                $details[] = "Method body changed: {$before->name}::$name";
            }
        }
    }

    /**
     * Emit granular detail strings for a method signature change.
     *
     * @param string[] $details
     */
    private function emitMethodSignatureDetails(
        string $className,
        string $methodName,
        MethodInfo $before,
        MethodInfo $after,
        array &$details,
    ): void {
        $label = "{$className}::{$methodName}";

        // Return type
        $bReturn = $this->normalizeType($before->returnType);
        $aReturn = $this->normalizeType($after->returnType);
        if ($bReturn !== $aReturn) {
            if ($bReturn === null && $aReturn !== null) {
                $details[] = "Method return type added: $label";
            } else {
                $details[] = "Method return type changed: $label";
            }
        }

        // Visibility
        if ($before->visibility !== $after->visibility) {
            if ($this->visibilityRank($after->visibility) > $this->visibilityRank($before->visibility)) {
                $details[] = "Method visibility loosened: $label";
            } else {
                $details[] = "Method visibility tightened: $label";
            }
        }

        // Abstract
        if (!$before->isAbstract && $after->isAbstract) {
            $details[] = "Method made abstract: $label";
        }

        // Final
        if (!$before->isFinal && $after->isFinal) {
            $details[] = "Method made final: $label";
        }

        // Static
        if ($before->isStatic !== $after->isStatic) {
            $details[] = "Method static changed: $label";
        }

        // Params
        $this->emitParamDetails($label, $before->params, $after->params, $details);

        // Constructor shorthand
        if ($methodName === '__construct') {
            $details[] = "Constructor changed: $label";
        }
    }

    /**
     * Emit granular detail strings for parameter changes.
     *
     * @param array<int, array<string, mixed>> $beforeParams
     * @param array<int, array<string, mixed>> $afterParams
     * @param string[] $details
     */
    private function emitParamDetails(string $label, array $beforeParams, array $afterParams, array &$details): void
    {
        $bCount = count($beforeParams);
        $aCount = count($afterParams);
        $maxCount = max($bCount, $aCount);

        // Index by position for comparison
        for ($i = 0; $i < $maxCount; $i++) {
            $bParam = $beforeParams[$i] ?? null;
            $aParam = $afterParams[$i] ?? null;

            if ($bParam === null && $aParam !== null) {
                // Param added
                $hasDefault = (bool) ($aParam['hasDefault'] ?? false);
                $isVariadic = (bool) ($aParam['variadic'] ?? false);
                if ($hasDefault || $isVariadic) {
                    $details[] = "Method param added (optional): $label";
                } else {
                    $details[] = "Method param added (required): $label";
                }
                continue;
            }

            if ($bParam !== null && $aParam === null) {
                $details[] = "Method param removed: $label";
                continue;
            }

            if ($bParam === null || $aParam === null) {
                continue;
            }

            // Type change
            $bType = $this->normalizeType(isset($bParam['type']) && is_string($bParam['type']) ? $bParam['type'] : null);
            $aType = $this->normalizeType(isset($aParam['type']) && is_string($aParam['type']) ? $aParam['type'] : null);
            if ($bType !== $aType) {
                if ($bType === null && $aType !== null) {
                    $details[] = "Method param type added: $label";
                } else {
                    $details[] = "Method param type changed: $label";
                }
            }

            // Name change
            $bName = $bParam['name'] ?? '';
            $aName = $aParam['name'] ?? '';
            if ($bName !== $aName) {
                $details[] = "Method param renamed: $label";
            }

            // Variadic/byRef change
            $bVariadic = (bool) ($bParam['variadic'] ?? false);
            $aVariadic = (bool) ($aParam['variadic'] ?? false);
            $bByRef = (bool) ($bParam['byRef'] ?? false);
            $aByRef = (bool) ($aParam['byRef'] ?? false);
            if ($bVariadic !== $aVariadic || $bByRef !== $aByRef) {
                $details[] = "Method param modifier changed: $label";
            }
        }
    }

    /**
     * @param string[] $details
     */
    private function compareProperties(
        ClassInfo $before,
        ClassInfo $after,
        bool &$signatureChanged,
        bool &$bodyChanged,
        bool &$membersAddedOrRemoved,
        array &$details,
    ): void {
        $beforeProps = [];
        foreach ($before->properties as $p) {
            $beforeProps[$p->name] = $p;
        }

        $afterProps = [];
        foreach ($after->properties as $p) {
            $afterProps[$p->name] = $p;
        }

        $allNames = array_unique(array_merge(array_keys($beforeProps), array_keys($afterProps)));

        foreach ($allNames as $name) {
            $bProp = $beforeProps[$name] ?? null;
            $aProp = $afterProps[$name] ?? null;

            if ($bProp === null) {
                $membersAddedOrRemoved = true;
                $details[] = "Property added: {$before->name}::\$$name";
                continue;
            }

            if ($aProp === null) {
                $membersAddedOrRemoved = true;
                $details[] = "Property removed: {$before->name}::\$$name";
                continue;
            }

            if (!$bProp->signatureEquals($aProp)) {
                $signatureChanged = true;
                $this->emitPropertySignatureDetails($before->name, $name, $bProp, $aProp, $details);
            }

            if (!$bProp->valueEquals($aProp)) {
                $bodyChanged = true;
                $details[] = "Property default value changed: {$before->name}::\$$name";
            }
        }
    }

    /**
     * Emit granular detail strings for a property signature change.
     *
     * @param string[] $details
     */
    private function emitPropertySignatureDetails(
        string $className,
        string $propName,
        PropertyInfo $before,
        PropertyInfo $after,
        array &$details,
    ): void {
        $label = "{$className}::\${$propName}";

        // Type
        $bType = $this->normalizeType($before->type);
        $aType = $this->normalizeType($after->type);
        if ($bType !== $aType) {
            if ($bType === null && $aType !== null) {
                $details[] = "Property type added: $label";
            } else {
                $details[] = "Property type changed: $label";
            }
        }

        // Visibility
        if ($before->visibility !== $after->visibility) {
            if ($this->visibilityRank($after->visibility) > $this->visibilityRank($before->visibility)) {
                $details[] = "Property visibility loosened: $label";
            } else {
                $details[] = "Property visibility tightened: $label";
            }
        }

        // Readonly
        if (!$before->isReadonly && $after->isReadonly) {
            $details[] = "Property made readonly: $label";
        }

        // Static
        if ($before->isStatic !== $after->isStatic) {
            $details[] = "Property static changed: $label";
        }
    }

    /**
     * @param string[] $details
     */
    private function compareConstants(
        ClassInfo $before,
        ClassInfo $after,
        bool &$signatureChanged,
        bool &$bodyChanged,
        bool &$membersAddedOrRemoved,
        array &$details,
    ): void {
        $beforeConsts = [];
        foreach ($before->constants as $c) {
            $beforeConsts[$c->name] = $c;
        }

        $afterConsts = [];
        foreach ($after->constants as $c) {
            $afterConsts[$c->name] = $c;
        }

        $allNames = array_unique(array_merge(array_keys($beforeConsts), array_keys($afterConsts)));

        foreach ($allNames as $name) {
            $bConst = $beforeConsts[$name] ?? null;
            $aConst = $afterConsts[$name] ?? null;

            if ($bConst === null) {
                $membersAddedOrRemoved = true;
                $details[] = "Constant added: {$before->name}::$name";
                continue;
            }

            if ($aConst === null) {
                $membersAddedOrRemoved = true;
                $details[] = "Constant removed: {$before->name}::$name";
                continue;
            }

            if (!$bConst->signatureEquals($aConst)) {
                $signatureChanged = true;
                $this->emitConstantSignatureDetails($before->name, $name, $bConst, $aConst, $details);
            }

            if (!$bConst->valueEquals($aConst)) {
                $bodyChanged = true;
                $details[] = "Constant value changed: {$before->name}::$name";
            }
        }
    }

    /**
     * Emit granular detail strings for a constant signature change.
     *
     * @param string[] $details
     */
    private function emitConstantSignatureDetails(
        string $className,
        string $constName,
        ConstantInfo $before,
        ConstantInfo $after,
        array &$details,
    ): void {
        $label = "{$className}::{$constName}";

        // Final
        if (!$before->isFinal && $after->isFinal) {
            $details[] = "Constant made final: $label";
        }

        // Type change (existing)
        $bType = $this->normalizeType($before->type);
        $aType = $this->normalizeType($after->type);
        if ($bType !== $aType) {
            $details[] = "Constant type changed: $label";
        }

        // Visibility
        if ($before->visibility !== $after->visibility) {
            $details[] = "Constant visibility changed: $label";
        }
    }

    private function visibilityRank(string $visibility): int
    {
        return match ($visibility) {
            'private' => 0,
            'protected' => 1,
            'public' => 2,
            default => -1,
        };
    }

    private function normalizeType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $type = ltrim($type, '\\');

        if (str_contains($type, '|')) {
            $parts = explode('|', $type);
            $parts = array_map(fn (string $pt) => ltrim($pt, '\\'), $parts);
            sort($parts);
            return implode('|', $parts);
        }

        if (str_contains($type, '&')) {
            $parts = explode('&', $type);
            $parts = array_map(fn (string $pt) => ltrim($pt, '\\'), $parts);
            sort($parts);
            return implode('&', $parts);
        }

        return $type;
    }
}
