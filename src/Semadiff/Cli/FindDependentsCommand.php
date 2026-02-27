<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Cli;

use Oro\UpgradeToolkit\Semadiff\Filter\NamespaceExcludeFilter;
use Oro\UpgradeToolkit\Semadiff\Resolver\DependencyResolver;
use Oro\UpgradeToolkit\Semadiff\Resolver\DependencyResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class FindDependentsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('find-dependents')
            ->setDescription('Finds classes that extend, implement, use (trait), or reference any of the given FQCNs')
            ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'Directory to scan for dependents')
            ->addOption('fqcn-file', 'f', InputOption::VALUE_REQUIRED, 'File containing FQCNs (one per line)')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output directory', './output')
            ->addOption('exclude', 'e', InputOption::VALUE_OPTIONAL, 'Comma-separated namespace patterns to exclude (e.g. "*\\Tests\\*,Vendor\\Fixtures\\*")')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Comma-separated dependency types to include: extends,implements,traits,uses (default: all)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dirOpt = $input->getOption('dir');
        $fqcnFileOpt = $input->getOption('fqcn-file');
        $outputOpt = $input->getOption('output');
        $excludeOpt = $input->getOption('exclude');
        $typeOpt = $input->getOption('type');

        $dir = is_string($dirOpt) ? $dirOpt : null;
        $fqcnFile = is_string($fqcnFileOpt) ? $fqcnFileOpt : null;
        $outputDir = is_string($outputOpt) ? $outputOpt : './output';
        $filter = NamespaceExcludeFilter::fromString(is_string($excludeOpt) ? $excludeOpt : null);
        $types = $this->parseTypes(is_string($typeOpt) ? $typeOpt : null);

        if ($dir === null || $fqcnFile === null) {
            $output->writeln('<error>Both --dir and --fqcn-file options are required.</error>');
            return Command::FAILURE;
        }

        if (!is_dir($dir)) {
            $output->writeln(sprintf('<error>Directory does not exist: %s</error>', $dir));
            return Command::FAILURE;
        }

        if (!is_file($fqcnFile)) {
            $output->writeln(sprintf('<error>FQCN file does not exist: %s</error>', $fqcnFile));
            return Command::FAILURE;
        }

        $lines = file($fqcnFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            $output->writeln(sprintf('<error>Could not read FQCN file: %s</error>', $fqcnFile));
            return Command::FAILURE;
        }

        $targetFqcns = array_values(array_filter(array_map('trim', $lines), fn (string $ln) => $ln !== ''));

        // Filter targets before scanning
        $excludedTargets = 0;
        if ($filter->hasPatterns()) {
            $beforeCount = count($targetFqcns);
            $targetFqcns = $filter->filterList($targetFqcns);
            $excludedTargets = $beforeCount - count($targetFqcns);
        }

        if ($targetFqcns === []) {
            $output->writeln('<comment>No targets remain after filtering — nothing to search for.</comment>');
            return Command::SUCCESS;
        }

        $output->writeln('<info>PHP Semadiff — Find Dependents</info>');
        $output->writeln(sprintf('Scanning: %s', $dir));
        $output->writeln(sprintf('Targets:  %d FQCNs', count($targetFqcns)));
        if ($filter->hasPatterns() && $excludedTargets > 0) {
            $output->writeln(sprintf('Excluded: %d targets by namespace pattern', $excludedTargets));
        }
        $output->writeln('');

        $resolver = new DependencyResolver();
        $result = $resolver->findDependents($dir, $targetFqcns, $types);

        // Filter results (both target keys and dependent values)
        if ($filter->hasPatterns()) {
            $result = new DependencyResult(
                $filter->filterGrouped($result->extends),
                $filter->filterGrouped($result->implements),
                $filter->filterGrouped($result->traits),
                $filter->filterGrouped($result->uses),
            );
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $typesSet = $types !== null ? array_flip($types) : array_flip(DependencyResolver::ALL_TYPES);

        $sections = [
            DependencyResolver::TYPE_EXTENDS    => ['label' => 'Extends',    'dir' => 'dependents_extends',    'data' => $result->extends,    'count' => $result->extendsCount()],
            DependencyResolver::TYPE_IMPLEMENTS => ['label' => 'Implements', 'dir' => 'dependents_implements', 'data' => $result->implements, 'count' => $result->implementsCount()],
            DependencyResolver::TYPE_TRAITS     => ['label' => 'Traits',     'dir' => 'dependents_traits',     'data' => $result->traits,     'count' => $result->traitsCount()],
            DependencyResolver::TYPE_USES       => ['label' => 'Uses',       'dir' => 'dependents_uses',       'data' => $result->uses,       'count' => $result->usesCount()],
        ];

        $output->writeln('<info>=== Results ===</info>');
        foreach ($sections as $type => $section) {
            if (!isset($typesSet[$type])) {
                continue;
            }
            $this->writeGroupedFiles($outputDir . '/' . $section['dir'], $section['data']);
            $output->writeln(sprintf(
                '  %-10s %d dependents across %d targets',
                $section['label'] . ':',
                $section['count'],
                count($section['data']),
            ));
        }
        $output->writeln('');

        foreach ($sections as $type => $section) {
            if (!isset($typesSet[$type])) {
                continue;
            }
            $this->printVerboseSection($output, $section['label'], $section['data']);
        }

        $output->writeln(sprintf('Output written to: %s/', $outputDir));

        return Command::SUCCESS;
    }

    /**
     * Write one flat file per target FQCN. Backslashes become double underscores:
     *   Vendor\Lib\Base → dependents_extends/Vendor__Lib__Base.txt
     *
     * @param string                  $baseDir  e.g. ./output/dependents_extends
     * @param array<string, string[]> $grouped  target FQCN → dependent FQCNs
     */
    private function writeGroupedFiles(string $baseDir, array $grouped): void
    {
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        foreach ($grouped as $targetFqcn => $dependents) {
            $fileName = str_replace('\\', '__', $targetFqcn) . '.txt';
            file_put_contents($baseDir . '/' . $fileName, implode("\n", $dependents) . "\n");
        }
    }

    /**
     * @return string[]|null null means all types
     */
    private function parseTypes(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $types = array_map('trim', explode(',', $raw));
        $valid = DependencyResolver::ALL_TYPES;
        $filtered = array_values(array_intersect($types, $valid));

        return $filtered !== [] ? $filtered : null;
    }

    /**
     * @param array<string, string[]> $grouped
     */
    private function printVerboseSection(OutputInterface $output, string $label, array $grouped): void
    {
        if ($grouped === []) {
            return;
        }

        $output->writeln(sprintf('<comment>--- %s ---</comment>', $label));
        $verbose = $output->isVerbose();
        foreach ($grouped as $target => $dependents) {
            $output->writeln(sprintf('  %s (%d)', $target, count($dependents)));
            if ($verbose) {
                foreach ($dependents as $dep) {
                    $output->writeln(sprintf('    %s', $dep));
                }
            }
        }
        $output->writeln('');
    }
}
