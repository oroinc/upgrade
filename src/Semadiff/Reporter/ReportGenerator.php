<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Reporter;

use Oro\UpgradeToolkit\Semadiff\Classifier\ChangeClassifier;
use UnexpectedValueException;

final class ReportGenerator
{
    /**
     * @param array<string, string> $classifications  filepath => 'cosmetic'|'signature'|'logic'
     * @param string[] $newFiles
     * @param string[] $deletedFiles
     * @param string[] $identicalFiles files that had no changes
     * @param array<string, string[]> $details per-file detail arrays
     * @param array<string, string[]> $parseErrors file => error messages
     * @param array<array{before: string, after: string, fqcns_before: string[], fqcns_after: string[]}> $movedFiles confirmed cosmetic-only moves
     * @param array<string, string> $pathToFqcn relativePath => FQCN mapping
     */
    public function generate(
        string $outputDir,
        array $classifications,
        array $newFiles,
        array $deletedFiles,
        array $identicalFiles,
        array $details = [],
        array $parseErrors = [],
        array $movedFiles = [],
        array $pathToFqcn = [],
    ): void {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $resolve = fn (string $path): string => $pathToFqcn[$path] ?? $path;

        $cosmetic = [];
        $signature = [];
        $logic = [];

        foreach ($classifications as $file => $category) {
            match ($category) {
                ChangeClassifier::COSMETIC => $cosmetic[] = $resolve($file),
                ChangeClassifier::SIGNATURE => $signature[] = $resolve($file),
                ChangeClassifier::LOGIC => $logic[] = $resolve($file),
                default => throw new UnexpectedValueException("Unknown change category: $category"),
            };
        }

        $newResolved = array_map($resolve, $newFiles);
        $deletedResolved = array_map($resolve, $deletedFiles);

        sort($cosmetic);
        sort($signature);
        sort($logic);
        sort($newResolved);
        sort($deletedResolved);

        file_put_contents($outputDir . '/cosmetic_only.txt', implode("\n", $cosmetic) . "\n");
        file_put_contents($outputDir . '/signature_only.txt', implode("\n", $signature) . "\n");
        file_put_contents($outputDir . '/logic_changes.txt', implode("\n", $logic) . "\n");
        file_put_contents($outputDir . '/new_files.txt', implode("\n", $newResolved) . "\n");
        file_put_contents($outputDir . '/deleted_files.txt', implode("\n", $deletedResolved) . "\n");

        // Moved files — output FQCNs (one line per FQCN pair, fallback to path if no FQCN found)
        $movedLines = [];
        foreach ($movedFiles as $m) {
            $beforeFqcns = $m['fqcns_before'];
            $afterFqcns = $m['fqcns_after'];

            if ($beforeFqcns !== [] && count($beforeFqcns) === count($afterFqcns)) {
                for ($i = 0, $count = count($beforeFqcns); $i < $count; $i++) {
                    $movedLines[] = $beforeFqcns[$i] . ' -> ' . $afterFqcns[$i];
                }
            } elseif ($afterFqcns !== []) {
                // Different count — list all after FQCNs with the before path as context
                foreach ($afterFqcns as $fqcn) {
                    $movedLines[] = $m['before'] . ' -> ' . $fqcn;
                }
            } else {
                // No FQCNs found — fallback to file paths
                $movedLines[] = $m['before'] . ' -> ' . $m['after'];
            }
        }
        sort($movedLines);
        file_put_contents($outputDir . '/moved_files.txt', implode("\n", $movedLines) . "\n");

        // Summary report
        $total = count($classifications);
        $summary = [];
        $summary[] = '=== PHP Semadiff - Summary Report ===';
        $summary[] = '';
        $summary[] = sprintf('Date: %s', date('Y-m-d H:i:s'));
        $summary[] = '';
        $summary[] = '--- File Counts ---';
        $summary[] = sprintf('Total paired files analyzed: %d', $total);
        $summary[] = sprintf('  Cosmetic only:     %d (%s%%)', count($cosmetic), $total > 0 ? round(count($cosmetic) / $total * 100, 1) : 0);
        $summary[] = sprintf('  Signature only:    %d (%s%%)', count($signature), $total > 0 ? round(count($signature) / $total * 100, 1) : 0);
        $summary[] = sprintf('  Logic changes:     %d (%s%%)', count($logic), $total > 0 ? round(count($logic) / $total * 100, 1) : 0);
        $summary[] = sprintf('  Identical (skip):  %d', count($identicalFiles));
        $summary[] = sprintf('New files:           %d', count($newResolved));
        $summary[] = sprintf('Deleted files:       %d', count($deletedResolved));
        $summary[] = sprintf('Moved files (cosmetic): %d', count($movedFiles));
        $summary[] = '';

        if ($parseErrors !== []) {
            $summary[] = '--- Parse Errors (classified as LOGIC for safety) ---';
            foreach ($parseErrors as $file => $errors) {
                $summary[] = '  ' . $resolve($file);
                foreach ($errors as $error) {
                    $summary[] = '    ERROR: ' . $error;
                }
            }
            $summary[] = '';
        }

        // Moved files
        if ($movedLines !== []) {
            $summary[] = '--- Moved Files ---';
            foreach ($movedLines as $line) {
                $summary[] = '  ' . $line;
            }
            $summary[] = '';
        }

        // Files with details
        if ($details !== []) {
            $summary[] = '--- Change Details ---';
            foreach ($details as $file => $fileDetails) {
                if ($fileDetails !== []) {
                    $category = $classifications[$file] ?? 'unknown';
                    $summary[] = sprintf('[%s] %s', strtoupper($category), $resolve($file));
                    foreach ($fileDetails as $detail) {
                        $summary[] = '  - ' . $detail;
                    }
                }
            }
            $summary[] = '';
        }

        $summary[] = '=== End of Report ===';

        file_put_contents($outputDir . '/summary_report.txt', implode("\n", $summary) . "\n");
    }
}
