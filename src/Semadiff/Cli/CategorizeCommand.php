<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Cli;

use Oro\UpgradeToolkit\Semadiff\Classifier\ChangeClassifier;
use Oro\UpgradeToolkit\Semadiff\Comparator\FileComparator;
use Oro\UpgradeToolkit\Semadiff\Extractor\ClassInfoExtractor;
use Oro\UpgradeToolkit\Semadiff\FileCollector;
use Oro\UpgradeToolkit\Semadiff\Filter\NamespaceExcludeFilter;
use Oro\UpgradeToolkit\Semadiff\Reporter\ReportGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CategorizeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('semadiff')
            ->setDescription('Classifies PHP file changes into cosmetic, signature, and logic risk categories using AST comparison')
            ->addOption('before', 'b', InputOption::VALUE_REQUIRED, 'Path to the before directory')
            ->addOption('after', 'a', InputOption::VALUE_REQUIRED, 'Path to the after directory')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output directory', './output')
            ->addOption('exclude', 'e', InputOption::VALUE_OPTIONAL, 'Comma-separated namespace patterns to exclude (e.g. "*\\Tests\\*,Vendor\\Fixtures\\*")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $beforeOpt = $input->getOption('before');
        $afterOpt = $input->getOption('after');
        $outputOpt = $input->getOption('output');
        $excludeOpt = $input->getOption('exclude');

        $beforeDir = is_string($beforeOpt) ? $beforeOpt : null;
        $afterDir = is_string($afterOpt) ? $afterOpt : null;
        $outputDir = is_string($outputOpt) ? $outputOpt : './output';
        $filter = NamespaceExcludeFilter::fromString(is_string($excludeOpt) ? $excludeOpt : null);

        if ($beforeDir === null || $afterDir === null) {
            $output->writeln('<error>Both --before and --after options are required.</error>');
            return Command::FAILURE;
        }

        if (!is_dir($beforeDir)) {
            $output->writeln(sprintf('<error>Before directory does not exist: %s</error>', $beforeDir));
            return Command::FAILURE;
        }

        if (!is_dir($afterDir)) {
            $output->writeln(sprintf('<error>After directory does not exist: %s</error>', $afterDir));
            return Command::FAILURE;
        }

        $output->writeln('<info>PHP Semadiff — Semantic PHP Change Classifier</info>');
        $output->writeln('');

        // Step 1: Collect files
        $output->writeln('Scanning directories...');
        $collector = new FileCollector();
        $files = $collector->collect($beforeDir, $afterDir);

        $output->writeln(sprintf('  Paired files:   %d', count($files['paired'])));
        $output->writeln(sprintf('  New files:      %d', count($files['new'])));
        $output->writeln(sprintf('  Deleted:        %d', count($files['deleted'])));
        $output->writeln(sprintf('  Move candidates: %d', count($files['moved'])));
        $output->writeln('');

        // Step 2: Compare and classify
        $comparator = new FileComparator();
        $classifier = new ChangeClassifier();
        $extractor = new ClassInfoExtractor();

        $classifications = [];
        $allDetails = [];
        $identicalFiles = [];
        /** @var array<string, string[]> file => error messages */
        $parseErrors = [];
        /** @var array<string, string> relativePath => FQCN (first class-like declaration) */
        $pathToFqcn = [];

        $movedFiles = [];

        $output->writeln('Analyzing files...');
        $progressBar = new ProgressBar($output, count($files['paired']) + count($files['moved']));
        $progressBar->setFormat(' %current%/%max% [%bar%]');
        $progressBar->start();

        foreach ($files['paired'] as $relativePath) {
            $progressBar->advance();

            $beforeFile = rtrim($beforeDir, '/') . '/' . $relativePath;
            $afterFile = rtrim($afterDir, '/') . '/' . $relativePath;

            $beforeCode = file_get_contents($beforeFile);
            $afterCode = file_get_contents($afterFile);

            if ($beforeCode === false || $afterCode === false) {
                $readErrors = [];
                if ($beforeCode === false) {
                    $readErrors[] = 'before: Could not read file';
                }
                if ($afterCode === false) {
                    $readErrors[] = 'after: Could not read file';
                }
                $parseErrors[$relativePath] = $readErrors;
                $classifications[$relativePath] = ChangeClassifier::LOGIC;
                continue;
            }

            // Extract FQCN from after code (current state)
            try {
                $fqcns = $extractor->extractFqcns($afterCode);
                if ($fqcns !== []) {
                    $pathToFqcn[$relativePath] = $fqcns[0];
                }
            } catch (\Throwable $e) {
                $parseErrors[$relativePath] = ['after: ' . $e->getMessage()];
            }

            // Skip identical files
            if ($beforeCode === $afterCode) {
                $identicalFiles[] = $relativePath;
                continue;
            }

            $result = $comparator->compare($beforeCode, $afterCode);

            if ($result->parseErrors !== []) {
                $parseErrors[$relativePath] = $result->parseErrors;
            }

            $category = $classifier->classify($result);
            $classifications[$relativePath] = $category;
            $allDetails[$relativePath] = $result->details;
        }

        // Process move candidates: verify they are cosmetic-only
        foreach ($files['moved'] as $moveCandidate) {
            $progressBar->advance();

            $beforeFile = rtrim($beforeDir, '/') . '/' . $moveCandidate['before'];
            $afterFile = rtrim($afterDir, '/') . '/' . $moveCandidate['after'];

            $beforeCode = file_get_contents($beforeFile);
            $afterCode = file_get_contents($afterFile);

            if ($beforeCode === false || $afterCode === false) {
                // Can't verify — put back as new/deleted
                $files['new'][] = $moveCandidate['after'];
                $files['deleted'][] = $moveCandidate['before'];
                continue;
            }

            $result = $comparator->compare($beforeCode, $afterCode);
            $category = $classifier->classify($result);

            if ($category === ChangeClassifier::COSMETIC) {
                try {
                    $moveCandidate['fqcns_before'] = $extractor->extractFqcns($beforeCode);
                    $moveCandidate['fqcns_after'] = $extractor->extractFqcns($afterCode);
                    $movedFiles[] = $moveCandidate;
                } catch (\Throwable $e) {
                    $files['new'][] = $moveCandidate['after'];
                    $files['deleted'][] = $moveCandidate['before'];
                }
            } else {
                // Not cosmetic — put back as new/deleted
                $files['new'][] = $moveCandidate['after'];
                $files['deleted'][] = $moveCandidate['before'];
            }
        }

        sort($files['new']);
        sort($files['deleted']);

        // Extract FQCNs for new files (from after dir)
        foreach ($files['new'] as $relativePath) {
            $code = file_get_contents(rtrim($afterDir, '/') . '/' . $relativePath);
            if ($code !== false) {
                try {
                    $fqcns = $extractor->extractFqcns($code);
                    if ($fqcns !== []) {
                        $pathToFqcn[$relativePath] = $fqcns[0];
                    }
                } catch (\Throwable $e) {
                    $parseErrors[$relativePath] = ['new: ' . $e->getMessage()];
                }
            }
        }

        // Extract FQCNs for deleted files (from before dir)
        foreach ($files['deleted'] as $relativePath) {
            $code = file_get_contents(rtrim($beforeDir, '/') . '/' . $relativePath);
            if ($code !== false) {
                try {
                    $fqcns = $extractor->extractFqcns($code);
                    if ($fqcns !== []) {
                        $pathToFqcn[$relativePath] = $fqcns[0];
                    }
                } catch (\Throwable $e) {
                    $parseErrors[$relativePath] = ['deleted: ' . $e->getMessage()];
                }
            }
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln('');

        // Exclude files without class-like declarations (no FQCN)
        $hasFqcn = fn (string $path): bool => isset($pathToFqcn[$path]);
        $skippedNoFqcn = 0;

        $prevCount = count($classifications);
        $classifications = array_filter($classifications, fn ($path) => $hasFqcn($path), ARRAY_FILTER_USE_KEY);
        $allDetails = array_filter($allDetails, fn ($path) => $hasFqcn($path), ARRAY_FILTER_USE_KEY);
        $skippedNoFqcn += $prevCount - count($classifications);

        $prevCount = count($files['new']);
        $files['new'] = array_values(array_filter($files['new'], $hasFqcn));
        $skippedNoFqcn += $prevCount - count($files['new']);

        $prevCount = count($files['deleted']);
        $files['deleted'] = array_values(array_filter($files['deleted'], $hasFqcn));
        $skippedNoFqcn += $prevCount - count($files['deleted']);

        $prevCount = count($movedFiles);
        $movedFiles = array_values(array_filter($movedFiles, fn ($move) => $move['fqcns_after'] !== []));
        $skippedNoFqcn += $prevCount - count($movedFiles);

        // Exclude files matching namespace patterns
        $skippedByExclude = 0;
        if ($filter->hasPatterns()) {
            $isNotExcluded = fn (string $path): bool =>
                !isset($pathToFqcn[$path]) || !$filter->isExcluded($pathToFqcn[$path]);

            $prevCount = count($classifications);
            $classifications = array_filter($classifications, fn ($path) => $isNotExcluded($path), ARRAY_FILTER_USE_KEY);
            $allDetails = array_filter($allDetails, fn ($path) => $isNotExcluded($path), ARRAY_FILTER_USE_KEY);
            $skippedByExclude += $prevCount - count($classifications);

            $prevCount = count($files['new']);
            $files['new'] = array_values(array_filter($files['new'], $isNotExcluded));
            $skippedByExclude += $prevCount - count($files['new']);

            $prevCount = count($files['deleted']);
            $files['deleted'] = array_values(array_filter($files['deleted'], $isNotExcluded));
            $skippedByExclude += $prevCount - count($files['deleted']);

            $prevCount = count($movedFiles);
            $movedFiles = array_values(array_filter($movedFiles, function ($move) use ($filter) {
                foreach ($move['fqcns_after'] as $fqcn) {
                    if ($filter->isExcluded($fqcn)) {
                        return false;
                    }
                }
                return true;
            }));
            $skippedByExclude += $prevCount - count($movedFiles);
        }

        // Print parse errors prominently so they can be investigated
        if ($parseErrors !== []) {
            $output->writeln('<error>=== Parse Errors ===</error>');
            $output->writeln('');
            foreach ($parseErrors as $file => $errors) {
                $output->writeln(sprintf('  <comment>%s</comment>', $file));
                foreach ($errors as $error) {
                    $output->writeln(sprintf('    <fg=red>%s</>', $error));
                }
            }
            $output->writeln('');
            $output->writeln(sprintf(
                '<comment>%d file(s) had parse errors — classified as LOGIC for safety</comment>',
                count($parseErrors),
            ));
            $output->writeln('');
        }

        // Step 3: Generate report
        $output->writeln('Generating report...');
        $reporter = new ReportGenerator();
        $reporter->generate(
            $outputDir,
            $classifications,
            $files['new'],
            $files['deleted'],
            $identicalFiles,
            $allDetails,
            $parseErrors,
            $movedFiles,
            $pathToFqcn,
        );

        // Print summary
        $cosmetic = count(array_filter($classifications, fn ($cat) => $cat === ChangeClassifier::COSMETIC));
        $signature = count(array_filter($classifications, fn ($cat) => $cat === ChangeClassifier::SIGNATURE));
        $logic = count(array_filter($classifications, fn ($cat) => $cat === ChangeClassifier::LOGIC));

        $output->writeln('');
        $output->writeln('<info>=== Results ===</info>');
        $output->writeln(sprintf('  Cosmetic:   <fg=green>%d</>', $cosmetic));
        $output->writeln(sprintf('  Signature:  <fg=yellow>%d</>', $signature));
        $output->writeln(sprintf('  Logic:      <fg=red>%d</>', $logic));
        $output->writeln(sprintf('  Identical:  %d (skipped)', count($identicalFiles)));
        $output->writeln(sprintf('  New files:  %d', count($files['new'])));
        $output->writeln(sprintf('  Deleted:    %d', count($files['deleted'])));
        $output->writeln(sprintf('  Moved:      %d (cosmetic only)', count($movedFiles)));

        if ($parseErrors !== []) {
            $output->writeln(sprintf('  Parse errs: <fg=red>%d</> (see above)', count($parseErrors)));
        }

        $output->writeln(sprintf('  No FQCN:    %d (excluded)', $skippedNoFqcn));

        if ($skippedByExclude > 0) {
            $output->writeln(sprintf('  Excluded:   %d (by namespace pattern)', $skippedByExclude));
        }

        $output->writeln('');
        $output->writeln(sprintf('Output written to: %s/', $outputDir));

        return Command::SUCCESS;
    }
}
