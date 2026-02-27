<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileObject;

final class FileCollector
{
    private const EXCLUDED_PREFIXES = [
        'rector/rector/',
    ];

    /**
     * @return array{paired: string[], new: string[], deleted: string[], moved: array<array{before: string, after: string}>, beforePaths: FqcnPathMap, afterPaths: FqcnPathMap}
     *         Each entry is a relative path (e.g., "src/Foo/Bar.php")
     *         Moved entries are candidate pairs where basename matches uniquely in both new and deleted lists
     */
    public function collect(string $beforeDir, string $afterDir): array
    {
        $beforePaths = new FqcnPathMap();
        $afterPaths = new FqcnPathMap();
        $beforeFiles = $this->scanPhpFiles($beforeDir, $beforePaths);
        $afterFiles = $this->scanPhpFiles($afterDir, $afterPaths);

        $beforeSet = array_flip($beforeFiles);
        $afterSet = array_flip($afterFiles);

        $paired = [];
        $new = [];
        $deleted = [];

        foreach ($afterFiles as $file) {
            if (isset($beforeSet[$file])) {
                $paired[] = $file;
            } else {
                $new[] = $file;
            }
        }

        foreach ($beforeFiles as $file) {
            if (!isset($afterSet[$file])) {
                $deleted[] = $file;
            }
        }

        // Detect move candidates: unique basename match between new and deleted
        $moved = [];
        $newByBasename = $this->groupByBasename($new);
        $deletedByBasename = $this->groupByBasename($deleted);

        foreach ($newByBasename as $basename => $newPaths) {
            if (
                count($newPaths) === 1
                && isset($deletedByBasename[$basename])
                && count($deletedByBasename[$basename]) === 1
            ) {
                $moved[] = [
                    'before' => $deletedByBasename[$basename][0],
                    'after' => $newPaths[0],
                ];
                $new = array_values(array_diff($new, $newPaths));
                $deleted = array_values(array_diff($deleted, $deletedByBasename[$basename]));
            }
        }

        // Second pass: FQCN-based move detection for remaining unmatched files
        $fqcnMoves = $this->detectMovedByFqcn($deleted, $new, $beforeDir, $afterDir);
        $moved = array_merge($moved, $fqcnMoves);

        sort($paired);
        sort($new);
        sort($deleted);
        usort($moved, fn (array $left, array $right) => $left['after'] <=> $right['after']);

        return [
            'paired' => $paired,
            'new' => $new,
            'deleted' => $deleted,
            'moved' => $moved,
            'beforePaths' => $beforePaths,
            'afterPaths' => $afterPaths,
        ];
    }

    /**
     * @param string[] $paths
     * @return array<string, string[]> basename => list of paths with that basename
     */
    private function groupByBasename(array $paths): array
    {
        $groups = [];
        foreach ($paths as $path) {
            $groups[basename($path)][] = $path;
        }
        return $groups;
    }

    /**
     * @return string[] relative paths to PHP files
     */
    private function scanPhpFiles(string $directory, ?FqcnPathMap $pathMap = null): array
    {
        $directory = rtrim($directory, '/');
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = substr($file->getPathname(), strlen($directory) + 1);
                if (!$this->isExcluded($relativePath)) {
                    $files[] = $relativePath;

                    if ($pathMap !== null) {
                        $fqcn = $this->extractFqcnFromFile($file->getPathname());
                        if ($fqcn !== null) {
                            $pathMap->set($fqcn, $file->getPathname());
                        }
                    }
                }
            }
        }

        return $files;
    }

    /**
     * @param string[] $deleted relative paths (mutated: matched entries removed)
     * @param string[] $new     relative paths (mutated: matched entries removed)
     * @return array<array{before: string, after: string}>
     */
    private function detectMovedByFqcn(array &$deleted, array &$new, string $beforeDir, string $afterDir): array
    {
        /** @var array<string, string[]> FQCN => list of relative paths */
        $deletedByFqcn = [];
        foreach ($deleted as $path) {
            $fqcn = $this->extractFqcnFromFile($beforeDir . '/' . $path);
            if ($fqcn !== null) {
                $deletedByFqcn[$fqcn][] = $path;
            }
        }

        /** @var array<string, string[]> FQCN => list of relative paths */
        $newByFqcn = [];
        foreach ($new as $path) {
            $fqcn = $this->extractFqcnFromFile($afterDir . '/' . $path);
            if ($fqcn !== null) {
                $newByFqcn[$fqcn][] = $path;
            }
        }

        $moved = [];
        $matchedDeleted = [];
        $matchedNew = [];

        foreach ($deletedByFqcn as $fqcn => $deletedPaths) {
            if (
                count($deletedPaths) === 1
                && isset($newByFqcn[$fqcn])
                && count($newByFqcn[$fqcn]) === 1
            ) {
                $moved[] = [
                    'before' => $deletedPaths[0],
                    'after' => $newByFqcn[$fqcn][0],
                ];
                $matchedDeleted[] = $deletedPaths[0];
                $matchedNew[] = $newByFqcn[$fqcn][0];
            }
        }

        if ($matchedDeleted !== []) {
            $deleted = array_values(array_diff($deleted, $matchedDeleted));
            $new = array_values(array_diff($new, $matchedNew));
        }

        return $moved;
    }

    private function extractFqcnFromFile(string $filePath): ?string
    {
        $file = new SplFileObject($filePath);
        $namespace = null;
        $className = null;
        $lineCount = 0;

        while (!$file->eof() && $lineCount < 120) {
            $line = $file->fgets();
            $lineCount++;

            if ($namespace === null && preg_match('/^namespace\s+(.+?);/', $line, $matches) === 1) {
                $namespace = $matches[1];
            }

            if (preg_match('/^(?:final |abstract |readonly )*(?:class|interface|trait|enum)\s+(\w+)/', $line, $matches) === 1) {
                $className = $matches[1];
                break;
            }
        }

        if ($className === null) {
            return null;
        }

        return $namespace !== null ? $namespace . '\\' . $className : $className;
    }

    private function isExcluded(string $relativePath): bool
    {
        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($relativePath, $prefix)) {
                return true;
            }
        }

        // Exclude nested vendor directories (e.g., oro/upgrade-toolkit/vendor/*)
        if (str_contains($relativePath, '/vendor/')) {
            return true;
        }

        return false;
    }
}
