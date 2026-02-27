<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Analysis;

use Oro\UpgradeToolkit\Semadiff\Extractor\ClassInfo;
use Oro\UpgradeToolkit\Semadiff\Extractor\ClassInfoExtractor;
use Oro\UpgradeToolkit\Semadiff\FqcnPathMap;
use Oro\UpgradeToolkit\Semadiff\Git\DivergenceAnalyzer;

final class InheritanceFilter
{
    private ClassInfoExtractor $extractor;
    private DivergenceAnalyzer $analyzer;

    public function __construct()
    {
        $this->extractor = new ClassInfoExtractor();
        $this->analyzer = new DivergenceAnalyzer();
    }

    /**
     * Filter out "removed" details where the member actually exists on a parent class.
     *
     * @param string[] $details        All detail strings from FileComparator
     * @param string   $afterDir       Path to the after vendor directory
     * @param string   $vendorFqcn     The vendor class FQCN
     * @param string|null $afterFilePath Known file path for the after-version class (skips FQCN lookup)
     * @param string[] $warnings       Collected warnings (output parameter)
     * @param FqcnPathMap|null $afterPaths   Pre-built FQCN→path map for after-directory
     * @return string[]
     */
    public function filterInheritedRemovals(
        array $details,
        string $afterDir,
        string $vendorFqcn,
        ?string $afterFilePath = null,
        array &$warnings = [],
        ?FqcnPathMap $afterPaths = null,
    ): array {
        $removedDetails = [];
        foreach ($details as $detail) {
            if (
                str_starts_with($detail, 'Method removed: ')
                || str_starts_with($detail, 'Property removed: ')
                || str_starts_with($detail, 'Constant removed: ')
            ) {
                $removedDetails[] = $detail;
            }
        }

        if ($removedDetails === []) {
            return $details;
        }

        // Load child class
        if ($afterFilePath !== null) {
            $classInfo = $this->loadClassInfoFromFile($afterFilePath, $warnings);
        } else {
            $classInfo = $this->loadClassInfo($afterDir, $vendorFqcn, $warnings, $afterPaths);
        }

        if ($classInfo === null || $classInfo->extends === null) {
            return $details;
        }

        $parentFqcn = $this->resolveParentFqcn($classInfo->extends, $vendorFqcn);

        // Load parent class
        $parentInfo = $this->loadClassInfo($afterDir, $parentFqcn, $warnings, $afterPaths);

        if ($parentInfo === null) {
            $warnings[] = sprintf(
                'InheritanceFilter: Could not load parent class %s for %s',
                $parentFqcn,
                $vendorFqcn,
            );

            return $details;
        }

        $inheritedRemovals = [];
        foreach ($removedDetails as $detail) {
            $memberName = $this->extractMemberName($detail);
            if ($memberName === null) {
                continue;
            }

            if ($this->memberInheritedFromParent($detail, $memberName, $parentInfo)) {
                $inheritedRemovals[$detail] = true;
            }
        }

        if ($inheritedRemovals === []) {
            return $details;
        }

        return array_values(array_filter(
            $details,
            static fn (string $detail) => !isset($inheritedRemovals[$detail]),
        ));
    }

    /**
     * Resolve a parent class name to a fully qualified class name.
     *
     * If the extends value already contains a namespace separator, treat it as fully qualified.
     * Otherwise, prepend the child class's namespace.
     */
    private function resolveParentFqcn(string $extends, string $childFqcn): string
    {
        if (str_contains($extends, '\\')) {
            return ltrim($extends, '\\');
        }

        $lastSep = strrpos($childFqcn, '\\');
        if ($lastSep === false) {
            return $extends;
        }

        return substr($childFqcn, 0, $lastSep + 1) . $extends;
    }

    /**
     * @param string[] $warnings
     */
    private function loadClassInfo(string $dir, string $fqcn, array &$warnings, ?FqcnPathMap $pathMap = null): ?ClassInfo
    {
        $file = $this->analyzer->findFileForFqcn($dir, $fqcn, $pathMap);
        if ($file === null) {
            return null;
        }

        return $this->loadClassInfoFromFile($file, $warnings);
    }

    /**
     * @param string[] $warnings
     */
    private function loadClassInfoFromFile(string $filePath, array &$warnings): ?ClassInfo
    {
        $code = file_get_contents($filePath);
        if ($code === false) {
            return null;
        }

        try {
            $classes = $this->extractor->extract($code);
        } catch (\Throwable $e) {
            $warnings[] = sprintf('InheritanceFilter: Failed to parse %s: %s', $filePath, $e->getMessage());

            return null;
        }

        return $classes[0] ?? null;
    }

    private function extractMemberName(string $detail): ?string
    {
        $colonPos = strpos($detail, '::');
        if ($colonPos === false) {
            return null;
        }

        return substr($detail, $colonPos + 2);
    }

    private function memberInheritedFromParent(string $detail, string $memberName, ClassInfo $parentInfo): bool
    {
        if (str_starts_with($detail, 'Method removed: ')) {
            $method = $parentInfo->getMethod($memberName);

            return $method !== null && $method->visibility !== 'private';
        }

        if (str_starts_with($detail, 'Property removed: ')) {
            $propName = ltrim($memberName, '$');
            $property = $parentInfo->getProperty($propName);

            return $property !== null && $property->visibility !== 'private';
        }

        if (str_starts_with($detail, 'Constant removed: ')) {
            $constant = $parentInfo->getConstant($memberName);

            return $constant !== null && $constant->visibility !== 'private';
        }

        return false;
    }
}
