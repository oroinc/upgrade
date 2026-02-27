<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Cli;

use Oro\UpgradeToolkit\Semadiff\Analysis\BcDetailFilter;
use Oro\UpgradeToolkit\Semadiff\Analysis\InheritanceFilter;
use Oro\UpgradeToolkit\Semadiff\Analysis\ResolutionChecker;
use Oro\UpgradeToolkit\Semadiff\Classifier\ChangeClassifier;
use Oro\UpgradeToolkit\Semadiff\Cli\Report\MarkdownReportWriter;
use Oro\UpgradeToolkit\Semadiff\Cli\Report\ReportData;
use Oro\UpgradeToolkit\Semadiff\Comparator\FileComparator;
use Oro\UpgradeToolkit\Semadiff\Extractor\ClassInfoExtractor;
use Oro\UpgradeToolkit\Semadiff\FileCollector;
use Oro\UpgradeToolkit\Semadiff\Filter\NamespaceExcludeFilter;
use Oro\UpgradeToolkit\Semadiff\FqcnPathMap;
use Oro\UpgradeToolkit\Semadiff\Git\DivergenceAnalyzer;
use Oro\UpgradeToolkit\Semadiff\Resolver\DependencyResolver;
use Oro\UpgradeToolkit\Semadiff\Resolver\DependencyResult;
use Oro\UpgradeToolkit\Semadiff\Resolver\UsageAnalyzer;
use Oro\UpgradeToolkit\Semadiff\Resolver\UsageInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ReportCommand extends Command
{
    // ── Logic data ──────────────────────────────────────────────────────

    /** @var array<string, array{details: string[], path: string}> */
    private array $fqcnInfo = [];

    /** @var array<string, array{extends: string[], implements: string[], traits: string[]}> */
    private array $fqcnDependents = [];

    private int $totalAffected = 0;

    /** @var array<string, string> */
    private array $changes = [];

    private int $filesAnalyzed = 0;

    // ── BC data ─────────────────────────────────────────────────────────

    /** @var array<string, array{details: string[], path: string, changedMethods: string[], constructorChanged: bool}> */
    private array $signatureInfo = [];

    /** @var array<string, string[]> */
    private array $removedMembers = [];

    /** @var string[] */
    private array $deletedFqcns = [];

    /** @var array<string, UsageInfo[]> */
    private array $bcUsageMap = [];

    /** @var array<string, string[]> */
    private array $removedMemberDeps = [];

    /** @var array<string, array{extends: string[], implements: string[], traits: string[], uses: string[]}> */
    private array $bcAllDeps = [];

    /** @var array<string, string[]> */
    private array $deletedRefs = [];

    /** @var array{vendorItems: array<string, array{details: string[], items: list<array<string, mixed>>}>, deletedItems: array<string, array{refs: list<array{depFqcn: string, resolved: bool}>}>, totalItems: int, resolvedItems: int} */
    private array $bcResults = ['vendorItems' => [], 'deletedItems' => [], 'totalItems' => 0, 'resolvedItems' => 0];

    /** @var array{before: FqcnPathMap, after: FqcnPathMap, project: FqcnPathMap} */
    private array $pathMaps;

    protected function configure(): void
    {
        $this
            ->setName('report')
            ->setDescription('Generates an upgrade analysis report: logic changes with dependents and git patches')
            ->addOption('before', 'b', InputOption::VALUE_REQUIRED, 'Path to the "before" directory (git repo)')
            ->addOption('after', 'a', InputOption::VALUE_REQUIRED, 'Path to the "after" directory (git repo)')
            ->addOption('dir', 'd', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Project source directories to scan for dependents (repeatable)')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output markdown file path', './report.md')
            ->addOption('exclude', 'e', InputOption::VALUE_OPTIONAL, 'Comma-separated namespace patterns to exclude')
            ->addOption('sections', 's', InputOption::VALUE_OPTIONAL, 'Sections to include: bc, logic, all', 'all')
            ->addOption('cache-dir', null, InputOption::VALUE_OPTIONAL, 'Directory for caching analysis results', sys_get_temp_dir() . '/php-semadiff-cache')
            ->addOption('diff', null, InputOption::VALUE_NONE, 'Include git diffs in report (slower)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $beforeOpt = $input->getOption('before');
        $afterOpt = $input->getOption('after');
        $projectOpt = $input->getOption('dir');
        $outputOpt = $input->getOption('output');
        $excludeOpt = $input->getOption('exclude');
        $sectionsOpt = $input->getOption('sections');
        $cacheDirOpt = $input->getOption('cache-dir');
        $runDiff = (bool) $input->getOption('diff');

        $beforeDir = is_string($beforeOpt) ? $beforeOpt : null;
        $afterDir = is_string($afterOpt) ? $afterOpt : null;
        /** @var string[] $projectDirs */
        $projectDirs = is_array($projectOpt) ? array_filter($projectOpt, 'is_string') : [];
        $outputFile = is_string($outputOpt) ? $outputOpt : './report.md';
        $cacheDir = is_string($cacheDirOpt) ? $cacheDirOpt : sys_get_temp_dir() . '/php-semadiff-cache';
        $filter = NamespaceExcludeFilter::fromString(is_string($excludeOpt) ? $excludeOpt : null);
        $sections = is_string($sectionsOpt) ? $sectionsOpt : 'all';

        $runLogic = $sections === 'all' || $sections === 'logic';
        $runBc = $sections === 'all' || $sections === 'bc';

        if ($beforeDir === null || $afterDir === null || $projectDirs === []) {
            $output->writeln('<error>Options --before, --after, and at least one --dir are required.</error>');
            return Command::FAILURE;
        }

        foreach (['before' => $beforeDir, 'after' => $afterDir] as $label => $dir) {
            if (!is_dir($dir)) {
                $output->writeln(sprintf('<error>%s directory does not exist: %s</error>', ucfirst($label), $dir));
                return Command::FAILURE;
            }
        }
        foreach ($projectDirs as $dir) {
            if (!is_dir($dir)) {
                $output->writeln(sprintf('<error>dir directory does not exist: %s</error>', $dir));
                return Command::FAILURE;
            }
        }

        $output->writeln('<info>PHP Semadiff — Upgrade Analysis Report</info>');
        $output->writeln('');

        $this->resetState();

        $excludeStr = is_string($excludeOpt) ? $excludeOpt : '';
        $dirKeys = array_map(static fn (string $dir) => (string) realpath($dir), $projectDirs);
        sort($dirKeys);
        $cacheKey = md5((string) realpath($beforeDir) . '|' . (string) realpath($afterDir) . '|' . implode('|', $dirKeys) . '|' . $excludeStr);
        $cacheFile = rtrim($cacheDir, '/') . '/' . $cacheKey . '.json';

        if (is_file($cacheFile)) {
            unlink($cacheFile);
        }

        $this->analyzeFiles($output, $beforeDir, $afterDir, $filter, $runLogic, $runBc);
        $this->resolveAllDependents($output, $projectDirs, $filter, $runLogic, $runBc);
        $this->analyzeBcUsage($output, $projectDirs, $runBc);
        if ($runDiff) {
            $this->generatePatches($output, $beforeDir, $afterDir, $runBc, $this->pathMaps['after'], $this->pathMaps['before']);
        }
        $this->saveCache($cacheDir, $cacheFile);

        if (!$runDiff) {
            $this->changes = [];
        }

        $this->runResolutionCheck($output, $afterDir, $projectDirs, $runBc, $this->pathMaps['after']);

        // ── Write report ─────────────────────────────────────────────────
        $output->writeln('<comment>Writing report...</comment>');

        (new MarkdownReportWriter())->write(new ReportData(
            $outputFile,
            $this->filesAnalyzed,
            $runLogic,
            $runBc,
            $this->fqcnInfo,
            $this->fqcnDependents,
            $this->changes,
            $this->signatureInfo,
            $this->removedMembers,
            $this->deletedFqcns,
            $this->bcAllDeps,
            $this->deletedRefs,
            $this->bcResults,
        ));

        $output->writeln('');
        $output->writeln('<info>=== Report Generated ===</info>');
        if ($runLogic) {
            $output->writeln(sprintf('  Logic changes: <fg=yellow>%d</> (%d with dependents)', count($this->fqcnInfo), count($this->fqcnDependents)));
        }
        if ($runBc) {
            $remaining = $this->bcResults['totalItems'] - $this->bcResults['resolvedItems'];
            $output->writeln(sprintf('  BC items: <fg=yellow>%d</> total, %d remaining, %d resolved', $this->bcResults['totalItems'], $remaining, $this->bcResults['resolvedItems']));
        }
        $output->writeln('');
        $output->writeln(sprintf('Report: %s', $outputFile));

        return Command::SUCCESS;
    }

    // ── Phase methods ───────────────────────────────────────────────────

    private function resetState(): void
    {
        $this->fqcnInfo = [];
        $this->fqcnDependents = [];
        $this->totalAffected = 0;
        $this->changes = [];
        $this->filesAnalyzed = 0;
        $this->signatureInfo = [];
        $this->removedMembers = [];
        $this->deletedFqcns = [];
        $this->bcUsageMap = [];
        $this->removedMemberDeps = [];
        $this->bcAllDeps = [];
        $this->deletedRefs = [];
        $this->bcResults = ['vendorItems' => [], 'deletedItems' => [], 'totalItems' => 0, 'resolvedItems' => 0];
        $this->pathMaps = [
            'before' => new FqcnPathMap(),
            'after' => new FqcnPathMap(),
            'project' => new FqcnPathMap(),
        ];
    }

    /**
     * Phase 1: Analyze file changes between before/after directories.
     */
    private function analyzeFiles(
        OutputInterface $output,
        string $beforeDir,
        string $afterDir,
        NamespaceExcludeFilter $filter,
        bool $runLogic,
        bool $runBc,
    ): void {
        $output->writeln('<comment>[1/3] Analyzing file changes...</comment>');

        $collector = new FileCollector();
        $files = $collector->collect($beforeDir, $afterDir);
        $this->filesAnalyzed = count($files['paired']);
        $this->pathMaps['before'] = $files['beforePaths'];
        $this->pathMaps['after'] = $files['afterPaths'];

        $comparator = new FileComparator();
        $classifier = new ChangeClassifier();
        $extractor = new ClassInfoExtractor();
        $bcFilter = new BcDetailFilter();

        /** @var array<string, string> $removedMemberPaths fqcn → afterFilePath */
        $removedMemberPaths = [];

        $progressBar = new ProgressBar($output, count($files['paired']));
        $progressBar->setFormat(' %current%/%max% [%bar%]');
        $progressBar->start();

        foreach ($files['paired'] as $relativePath) {
            $progressBar->advance();

            $beforeFile = rtrim($beforeDir, '/') . '/' . $relativePath;
            $afterFile = rtrim($afterDir, '/') . '/' . $relativePath;

            $beforeCode = file_get_contents($beforeFile);
            $afterCode = file_get_contents($afterFile);

            if ($beforeCode === false || $afterCode === false || $beforeCode === $afterCode) {
                continue;
            }

            try {
                $afterAst = $extractor->parse($afterCode);
                if ($afterAst === null) {
                    continue;
                }
                $fqcns = $extractor->extractFqcnsFromAst($afterAst);
            } catch (\Throwable $e) {
                $output->writeln(sprintf(
                    '<comment>  Warning: Failed to parse %s: %s</comment>',
                    $relativePath,
                    $e->getMessage(),
                ));
                continue;
            }
            if ($fqcns === []) {
                continue;
            }

            $result = $comparator->compareWithParsedAfter($beforeCode, $afterCode, $afterAst);
            $category = $classifier->classify($result);

            $fqcn = $fqcns[0];

            if ($filter->hasPatterns() && $filter->isExcluded($fqcn)) {
                continue;
            }

            if ($category === ChangeClassifier::COSMETIC) {
                continue;
            }

            if ($runLogic && $category === ChangeClassifier::LOGIC) {
                $this->fqcnInfo[$fqcn] = [
                    'details' => $result->details,
                    'path' => $relativePath,
                ];
            }

            if ($runBc) {
                $bcDetails = $bcFilter->filterBcDetails($result->details);
                $removedDetails = $bcFilter->filterRemovedDetails($result->details);

                if ($bcDetails !== []) {
                    $extracted = $bcFilter->extractChangedMethods($bcDetails);
                    $this->signatureInfo[$fqcn] = [
                        'details' => $bcDetails,
                        'path' => $relativePath,
                        'changedMethods' => $extracted['methods'],
                        'constructorChanged' => $extracted['constructorChanged'],
                    ];
                }

                if ($removedDetails !== []) {
                    $this->removedMembers[$fqcn] = $removedDetails;
                    $removedMemberPaths[$fqcn] = $afterFile;
                }
            }
        }

        // Deleted files: extract FQCNs for BC
        if ($runBc && $files['deleted'] !== []) {
            foreach ($files['deleted'] as $deletedPath) {
                $deletedFile = rtrim($beforeDir, '/') . '/' . $deletedPath;
                $deletedCode = file_get_contents($deletedFile);
                if ($deletedCode === false) {
                    continue;
                }
                try {
                    $deletedFileFqcns = $extractor->extractFqcns($deletedCode);
                } catch (\Throwable $e) {
                    $output->writeln(sprintf(
                        '<comment>  Warning: Failed to parse deleted %s: %s</comment>',
                        $deletedPath,
                        $e->getMessage(),
                    ));
                    continue;
                }
                foreach ($deletedFileFqcns as $delFqcn) {
                    if (!$filter->hasPatterns() || !$filter->isExcluded($delFqcn)) {
                        $this->deletedFqcns[] = $delFqcn;
                    }
                }
            }
        }

        $progressBar->finish();
        $output->writeln('');

        if ($runBc && $this->removedMembers !== []) {
            $this->filterInheritedRemovals($output, $afterDir, $bcFilter, $removedMemberPaths, $this->pathMaps['after']);
        }

        if ($runLogic) {
            $output->writeln(sprintf('  Logic changes: <fg=yellow>%d</>', count($this->fqcnInfo)));
        }
        if ($runBc) {
            $output->writeln(sprintf('  Signature BC changes: <fg=yellow>%d</>', count($this->signatureInfo)));
            $output->writeln(sprintf('  Deleted classes: <fg=yellow>%d</>', count($this->deletedFqcns)));
            $output->writeln(sprintf('  Removed members: <fg=yellow>%d</>', count($this->removedMembers)));
        }
        $output->writeln('');
    }

    /**
     * Phase 2: Resolve all dependents (logic + BC + deleted) in a single directory scan.
     *
     * @param string[] $projectDirs
     */
    private function resolveAllDependents(
        OutputInterface $output,
        array $projectDirs,
        NamespaceExcludeFilter $filter,
        bool $runLogic,
        bool $runBc,
    ): void {
        $output->writeln('<comment>[2/3] Scanning for dependents...</comment>');

        $resolver = new DependencyResolver();
        $logicFqcns = $runLogic ? array_keys($this->fqcnInfo) : [];
        $bcFqcns = $runBc ? array_keys($this->signatureInfo) : [];
        $removedFqcns = $runBc ? array_keys($this->removedMembers) : [];
        $allBcFqcns = array_values(array_unique(array_merge($bcFqcns, $removedFqcns)));
        $deletedFqcns = $runBc ? $this->deletedFqcns : [];

        // Build queries for the unified scan
        /** @var array<string, array{fqcns: string[], types: string[]|null}> $queries */
        $queries = [];
        if ($logicFqcns !== []) {
            $queries['logic'] = [
                'fqcns' => $logicFqcns,
                'types' => [DependencyResolver::TYPE_EXTENDS, DependencyResolver::TYPE_IMPLEMENTS, DependencyResolver::TYPE_TRAITS],
            ];
        }
        if ($allBcFqcns !== []) {
            $queries['bc'] = [
                'fqcns' => $allBcFqcns,
                'types' => null,
            ];
        }
        if ($deletedFqcns !== []) {
            $queries['deleted'] = [
                'fqcns' => $deletedFqcns,
                'types' => [DependencyResolver::TYPE_USES],
            ];
        }

        if ($queries !== []) {
            $mergedResults = $this->findDependentsMultiInDirs($resolver, $projectDirs, $queries);
            $this->pathMaps['project'] = $mergedResults['pathMap'];

            // Process logic results
            if (isset($mergedResults['results']['logic'])) {
                $depResult = $mergedResults['results']['logic'];
                if ($filter->hasPatterns()) {
                    $depResult = new DependencyResult(
                        $filter->filterGrouped($depResult->extends),
                        $filter->filterGrouped($depResult->implements),
                        $filter->filterGrouped($depResult->traits),
                        [],
                    );
                }

                foreach ($logicFqcns as $fqcn) {
                    $extends = $depResult->extends[$fqcn] ?? [];
                    $implements = $depResult->implements[$fqcn] ?? [];
                    $traits = $depResult->traits[$fqcn] ?? [];

                    if ($extends === [] && $implements === [] && $traits === []) {
                        continue;
                    }

                    $this->fqcnDependents[$fqcn] = [
                        'extends' => $extends,
                        'implements' => $implements,
                        'traits' => $traits,
                    ];
                    $this->totalAffected += count($extends) + count($implements) + count($traits);
                }
            }

            // Process BC results
            if (isset($mergedResults['results']['bc'])) {
                $bcDepResult = $mergedResults['results']['bc'];
                if ($filter->hasPatterns()) {
                    $bcDepResult = new DependencyResult(
                        $filter->filterGrouped($bcDepResult->extends),
                        $filter->filterGrouped($bcDepResult->implements),
                        $filter->filterGrouped($bcDepResult->traits),
                        $filter->filterGrouped($bcDepResult->uses),
                    );
                }

                foreach ($allBcFqcns as $fqcn) {
                    $ext = $bcDepResult->extends[$fqcn] ?? [];
                    $impl = $bcDepResult->implements[$fqcn] ?? [];
                    $trt = $bcDepResult->traits[$fqcn] ?? [];
                    $use = $bcDepResult->uses[$fqcn] ?? [];

                    if ($ext === [] && $impl === [] && $trt === [] && $use === []) {
                        continue;
                    }

                    $this->bcAllDeps[$fqcn] = [
                        'extends' => $ext,
                        'implements' => $impl,
                        'traits' => $trt,
                        'uses' => $use,
                    ];
                }

                foreach ($removedFqcns as $fqcn) {
                    $flatDeps = array_values(array_unique(array_merge(
                        $bcDepResult->extends[$fqcn] ?? [],
                        $bcDepResult->implements[$fqcn] ?? [],
                        $bcDepResult->traits[$fqcn] ?? [],
                        $bcDepResult->uses[$fqcn] ?? [],
                    )));
                    if ($flatDeps !== []) {
                        $this->removedMemberDeps[$fqcn] = $flatDeps;
                    }
                }
            }

            // Process deleted class refs
            if (isset($mergedResults['results']['deleted'])) {
                $delDepResult = $mergedResults['results']['deleted'];
                if ($filter->hasPatterns()) {
                    $delDepResult = new DependencyResult([], [], [], $filter->filterGrouped($delDepResult->uses));
                }

                foreach ($deletedFqcns as $delFqcn) {
                    $refs = $delDepResult->uses[$delFqcn] ?? [];
                    if ($refs !== []) {
                        $this->deletedRefs[$delFqcn] = $refs;
                    }
                }
            }
        }

        if ($runLogic) {
            $output->writeln(sprintf(
                '  Logic: <fg=yellow>%d</> affected classes across <fg=yellow>%d</> changed FQCNs',
                $this->totalAffected,
                count($this->fqcnDependents),
            ));
        }
    }

    /**
     * Phase 2c: Run UsageAnalyzer on signature-change dependents.
     */
    /**
     * @param string[] $projectDirs
     */
    private function analyzeBcUsage(OutputInterface $output, array $projectDirs, bool $runBc): void
    {
        if (!$runBc) {
            return;
        }

        $usageAnalyzer = new UsageAnalyzer();
        $analyzer = new DivergenceAnalyzer();
        /** @var string[] $usageWarnings */
        $usageWarnings = [];

        foreach ($this->signatureInfo as $vendorFqcn => $info) {
            $deps = $this->bcAllDeps[$vendorFqcn] ?? ['extends' => [], 'implements' => [], 'traits' => [], 'uses' => []];

            $allDepFqcns = $this->flattenDeps($deps);

            foreach ($allDepFqcns as [$depFqcn, $relType]) {
                $depFile = $this->findFileInDirs($analyzer, $projectDirs, $depFqcn, $this->pathMaps['project']);
                if ($depFile === null) {
                    continue;
                }
                $depCode = file_get_contents($depFile);
                if ($depCode === false) {
                    continue;
                }

                $usage = $usageAnalyzer->analyze(
                    $vendorFqcn,
                    $info['changedMethods'],
                    $info['constructorChanged'],
                    $depFqcn,
                    $depCode,
                    $relType,
                    $usageWarnings,
                );

                if ($usage->hasAnyUsage()) {
                    $this->bcUsageMap[$vendorFqcn][] = $usage;
                }
            }
        }

        foreach ($usageWarnings as $warning) {
            $output->writeln(sprintf('  <comment>Warning: %s</comment>', $warning));
        }

        $output->writeln(sprintf(
            '  BC: <fg=yellow>%d</> signature changes affect project, <fg=yellow>%d</> deleted referenced, <fg=yellow>%d</> removed member references',
            count($this->bcAllDeps),
            count($this->deletedRefs),
            count($this->removedMemberDeps),
        ));

        $output->writeln('');
    }

    /**
     * Phase 3: Generate git patches for changed FQCNs.
     */
    private function generatePatches(
        OutputInterface $output,
        string $beforeDir,
        string $afterDir,
        bool $runBc,
        ?FqcnPathMap $afterPaths = null,
        ?FqcnPathMap $beforePathMap = null,
    ): void {
        $allPatchFqcns = array_keys($this->fqcnDependents);
        if ($runBc) {
            $allPatchFqcns = array_values(array_unique(array_merge(
                $allPatchFqcns,
                array_keys($this->bcAllDeps),
            )));
        }

        if ($allPatchFqcns === []) {
            return;
        }

        $output->writeln('<comment>[3/3] Generating changes...</comment>');

        $analyzer = new DivergenceAnalyzer();
        /** @var array<string, ?string> */
        $repoRootCache = [];

        /** @var array<string, string> */
        $pathLookup = [];
        foreach ($this->fqcnInfo as $fqcn => $info) {
            $pathLookup[$fqcn] = $info['path'];
        }
        foreach ($this->signatureInfo as $fqcn => $info) {
            if (!isset($pathLookup[$fqcn])) {
                $pathLookup[$fqcn] = $info['path'];
            }
        }

        $progressBar = new ProgressBar($output, count($allPatchFqcns));
        $progressBar->setFormat(' %current%/%max% [%bar%] %message%');
        $progressBar->setMessage('');
        $progressBar->start();

        $noGitPackages = [];

        foreach ($allPatchFqcns as $fqcn) {
            if (isset($this->changes[$fqcn])) {
                $progressBar->advance();
                continue;
            }

            $progressBar->setMessage($fqcn);
            $progressBar->advance();

            $change = $this->generateCompactPatch($analyzer, $repoRootCache, $beforeDir, $afterDir, $fqcn, $afterPaths, $beforePathMap);
            if ($change === null) {
                $afterFile = $analyzer->findFileForFqcn($afterDir, $fqcn, $afterPaths);
                if ($afterFile !== null) {
                    $afterRoot = $this->resolveRepoRoot($analyzer, $repoRootCache, $afterFile);
                    if ($afterRoot === null) {
                        $relDir = dirname(str_replace(rtrim($afterDir, '/') . '/', '', $afterFile));
                        $topDir = explode('/', $relDir)[0];
                        $noGitPackages[$topDir] = true;
                    }
                }
            }
            if ($change === null && isset($pathLookup[$fqcn])) {
                $change = $this->generateFallbackDiff($beforeDir, $afterDir, $pathLookup[$fqcn]);
            }
            if ($change !== null) {
                $this->changes[$fqcn] = $change;
            }
        }

        $progressBar->setMessage('');
        $progressBar->finish();
        $output->writeln('');

        if ($noGitPackages !== []) {
            $output->writeln(sprintf(
                '  <comment>Warning: %d package(s) have no git history (installed via dist): %s</comment>',
                count($noGitPackages),
                implode(', ', array_keys($noGitPackages)),
            ));
            $output->writeln('  <comment>Re-install with --prefer-source for commit history in change diffs.</comment>');
        }

        $output->writeln('');
    }

    private function saveCache(string $cacheDir, string $cacheFile): void
    {
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $cacheData = [
            'fqcnInfo' => $this->fqcnInfo,
            'fqcnDependents' => $this->fqcnDependents,
            'totalAffected' => $this->totalAffected,
            'changes' => $this->changes,
            'filesAnalyzed' => $this->filesAnalyzed,
            'signatureInfo' => $this->signatureInfo,
            'removedMembers' => $this->removedMembers,
            'deletedFqcns' => $this->deletedFqcns,
            'bcUsageMap' => $this->serializeUsageMap($this->bcUsageMap),
            'removedMemberDeps' => $this->removedMemberDeps,
            'bcAllDeps' => $this->bcAllDeps,
            'deletedRefs' => $this->deletedRefs,
        ];
        file_put_contents($cacheFile, json_encode($cacheData, JSON_THROW_ON_ERROR));
    }

    /**
     * Phase 5: Check BC resolutions.
     */
    /**
     * @param string[] $projectDirs
     */
    private function runResolutionCheck(
        OutputInterface $output,
        string $afterDir,
        array $projectDirs,
        bool $runBc,
        ?FqcnPathMap $afterPaths = null,
    ): void {
        if (!$runBc) {
            return;
        }

        if ($this->signatureInfo === [] && $this->removedMembers === [] && $this->deletedFqcns === []) {
            return;
        }

        $output->writeln('<comment>Checking BC resolutions...</comment>');

        $checker = new ResolutionChecker();
        /** @var string[] $resolutionWarnings */
        $resolutionWarnings = [];
        $this->bcResults = $checker->checkAll(
            $this->signatureInfo,
            $this->bcUsageMap,
            $this->removedMembers,
            $this->removedMemberDeps,
            $this->deletedFqcns,
            $this->deletedRefs,
            $afterDir,
            $projectDirs,
            $resolutionWarnings,
            $afterPaths,
            $this->pathMaps['project'],
        );

        foreach ($resolutionWarnings as $warning) {
            $output->writeln(sprintf('  <comment>Warning: %s</comment>', $warning));
        }

        $remaining = $this->bcResults['totalItems'] - $this->bcResults['resolvedItems'];
        $output->writeln(sprintf(
            '  BC items: <fg=yellow>%d</> total, <fg=yellow>%d</> remaining, <fg=green>%d</> resolved',
            $this->bcResults['totalItems'],
            $remaining,
            $this->bcResults['resolvedItems'],
        ));
        $output->writeln('');
    }

    // ── Git patch helpers ────────────────────────────────────────────────

    /**
     * Resolve repo root for a file, caching by directory to avoid redundant git calls.
     *
     * @param array<string, ?string> $cache dir => repo root
     */
    private function resolveRepoRoot(DivergenceAnalyzer $analyzer, array &$cache, string $filePath): ?string
    {
        $dir = dirname($filePath);
        if (!array_key_exists($dir, $cache)) {
            $cache[$dir] = $analyzer->getRepoRoot($dir);
        }

        return $cache[$dir];
    }

    /**
     * @param array<string, ?string> $repoRootCache
     */
    private function generateCompactPatch(
        DivergenceAnalyzer $analyzer,
        array &$repoRootCache,
        string $beforeDir,
        string $afterDir,
        string $fqcn,
        ?FqcnPathMap $afterPaths = null,
        ?FqcnPathMap $beforePathMap = null,
    ): ?string {
        $afterFile = $analyzer->findFileForFqcn($afterDir, $fqcn, $afterPaths);
        if ($afterFile === null) {
            return null;
        }

        $afterRepoRoot = $this->resolveRepoRoot($analyzer, $repoRootCache, $afterFile);
        if ($afterRepoRoot === null) {
            return null;
        }

        $afterRelPath = $analyzer->getRelativePath($afterRepoRoot, $afterFile);

        $beforeFile = $analyzer->findFileForFqcn($beforeDir, $fqcn, $beforePathMap);

        if ($beforeFile !== null) {
            $beforeRepoRoot = $this->resolveRepoRoot($analyzer, $repoRootCache, $beforeFile);
            if ($beforeRepoRoot !== null) {
                $beforeRelPath = $analyzer->getRelativePath($beforeRepoRoot, $beforeFile);
                $result = $analyzer->findDivergence($beforeRepoRoot, $beforeRelPath, $afterRepoRoot, $afterRelPath);
            } else {
                $result = ['common' => null, 'commits' => $analyzer->getFileCommits($afterRepoRoot, $afterRelPath)];
            }
        } else {
            $result = ['common' => null, 'commits' => $analyzer->getFileCommits($afterRepoRoot, $afterRelPath)];
        }

        $divergent = $result['commits'];
        $common = $result['common'];
        $uncommitted = $analyzer->getWorkingTreePatch($afterRepoRoot, $afterRelPath);
        $hasUncommitted = $uncommitted !== null;

        if ($divergent === [] && !$hasUncommitted) {
            return null;
        }

        $commitLines = [];
        foreach ($divergent as $commit) {
            $commitLines[] = sprintf('- `%s` %s', $commit->shortHash, $commit->subject);
        }
        if ($hasUncommitted) {
            $commitLines[] = '- _(uncommitted changes)_';
        }

        $diff = $analyzer->getDiffSince($afterRepoRoot, $afterRelPath, $common?->hash);

        $lines = [];
        $lines[] = implode("\n", $commitLines);
        $lines[] = '<!-- DIFF -->';
        if ($diff !== null) {
            $lines[] = $diff;
        }

        return implode("\n", $lines);
    }

    /**
     * Fallback: generate a plain unified diff between before/after files when git history is unavailable.
     */
    private function generateFallbackDiff(string $beforeDir, string $afterDir, string $relativePath): ?string
    {
        $beforeFile = rtrim($beforeDir, '/') . '/' . $relativePath;
        $afterFile = rtrim($afterDir, '/') . '/' . $relativePath;

        if (!is_file($beforeFile) || !is_file($afterFile)) {
            return null;
        }

        $cmd = sprintf(
            'diff -u %s %s | head -2000',
            escapeshellarg($beforeFile),
            escapeshellarg($afterFile),
        );

        $diff = shell_exec($cmd);
        if (!is_string($diff) || trim($diff) === '') {
            return null;
        }

        $diff = str_replace($beforeDir . '/', 'a/', $diff);
        $diff = str_replace($afterDir . '/', 'b/', $diff);

        return "<!-- DIFF -->\n" . $diff;
    }

    /**
     * Post-process removed members: filter out removals where the member is inherited from a parent class.
     * Updates $this->removedMembers and $this->signatureInfo in place.
     *
     * @param array<string, string> $filePaths fqcn → afterFilePath
     */
    private function filterInheritedRemovals(
        OutputInterface $output,
        string $afterDir,
        BcDetailFilter $bcFilter,
        array $filePaths,
        ?FqcnPathMap $afterPaths = null,
    ): void {
        $inheritanceFilter = new InheritanceFilter();
        /** @var string[] $warnings */
        $warnings = [];

        foreach ($this->removedMembers as $fqcn => $removedDetails) {
            $allDetails = array_merge(
                $this->signatureInfo[$fqcn]['details'] ?? [],
                $removedDetails,
            );

            $afterFilePath = $filePaths[$fqcn] ?? null;
            $filtered = $inheritanceFilter->filterInheritedRemovals($allDetails, $afterDir, $fqcn, $afterFilePath, $warnings, $afterPaths);
            if (count($filtered) === count($allDetails)) {
                continue;
            }

            // Re-extract removed details from filtered set
            $newRemoved = $bcFilter->filterRemovedDetails($filtered);
            if ($newRemoved === []) {
                unset($this->removedMembers[$fqcn]);
            } else {
                $this->removedMembers[$fqcn] = $newRemoved;
            }

            // Update signatureInfo if it exists for this FQCN
            if (isset($this->signatureInfo[$fqcn])) {
                $newBcDetails = $bcFilter->filterBcDetails($filtered);
                if ($newBcDetails === []) {
                    unset($this->signatureInfo[$fqcn]);
                } else {
                    $extracted = $bcFilter->extractChangedMethods($newBcDetails);
                    $this->signatureInfo[$fqcn]['details'] = $newBcDetails;
                    $this->signatureInfo[$fqcn]['changedMethods'] = $extracted['methods'];
                    $this->signatureInfo[$fqcn]['constructorChanged'] = $extracted['constructorChanged'];
                }
            }
        }

        foreach ($warnings as $warning) {
            $output->writeln(sprintf('  <comment>Warning: %s</comment>', $warning));
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Run findDependentsMulti across multiple directories and merge results + pathMaps.
     *
     * @param string[] $directories
     * @param array<string, array{fqcns: string[], types: string[]|null}> $queries
     * @return array{results: array<string, DependencyResult>, pathMap: FqcnPathMap}
     */
    private function findDependentsMultiInDirs(
        DependencyResolver $resolver,
        array $directories,
        array $queries,
    ): array {
        $mergedPathMap = new FqcnPathMap();
        /** @var array<string, DependencyResult> $mergedResults */
        $mergedResults = [];
        foreach (array_keys($queries) as $name) {
            $mergedResults[$name] = new DependencyResult([], [], [], []);
        }

        foreach ($directories as $dir) {
            $scan = $resolver->findDependentsMulti($dir, $queries);
            $mergedPathMap->merge($scan['pathMap']);
            foreach ($scan['results'] as $name => $result) {
                $mergedResults[$name] = $mergedResults[$name]->merge($result);
            }
        }

        return ['results' => $mergedResults, 'pathMap' => $mergedPathMap];
    }

    /**
     * Find a PHP file for an FQCN, searching multiple project directories.
     *
     * @param string[] $projectDirs
     */
    private function findFileInDirs(DivergenceAnalyzer $analyzer, array $projectDirs, string $fqcn, ?FqcnPathMap $pathMap = null): ?string
    {
        // Try path map first (O(1) lookup)
        $mapped = $pathMap?->get($fqcn);
        if ($mapped !== null) {
            return $mapped;
        }

        foreach ($projectDirs as $dir) {
            $file = $analyzer->findFileForFqcn($dir, $fqcn);
            if ($file !== null) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Flatten typed deps into [depFqcn, relationType] pairs.
     *
     * @param array{extends: string[], implements: string[], traits: string[], uses: string[]} $deps
     * @return list<array{0: string, 1: string}>
     */
    private function flattenDeps(array $deps): array
    {
        $result = [];
        $seen = [];

        $typeMap = [
            'extends' => DependencyResolver::TYPE_EXTENDS,
            'implements' => DependencyResolver::TYPE_IMPLEMENTS,
            'traits' => DependencyResolver::TYPE_TRAITS,
            'uses' => DependencyResolver::TYPE_USES,
        ];

        foreach ($typeMap as $key => $type) {
            foreach ($deps[$key] as $depFqcn) {
                if (!isset($seen[$depFqcn])) {
                    $result[] = [$depFqcn, $type];
                    $seen[$depFqcn] = true;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, UsageInfo[]> $bcUsageMap
     * @return array<string, list<array<string, mixed>>>
     */
    private function serializeUsageMap(array $bcUsageMap): array
    {
        $result = [];
        foreach ($bcUsageMap as $fqcn => $usages) {
            $result[$fqcn] = array_values(array_map(static fn (UsageInfo $usage) => $usage->toArray(), $usages));
        }

        return $result;
    }

}
