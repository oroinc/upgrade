<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Cli;

use Oro\UpgradeToolkit\Semadiff\Git\CommitInfo;
use Oro\UpgradeToolkit\Semadiff\Git\DivergenceAnalyzer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ClassDiffCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('class-diff')
            ->setDescription('Shows git format-patch style output for divergent commits touching a class')
            ->addOption('fqcn', 'c', InputOption::VALUE_REQUIRED, 'Fully qualified class name')
            ->addOption('before', 'b', InputOption::VALUE_REQUIRED, 'Path to the "before" directory (git repo)')
            ->addOption('after', 'a', InputOption::VALUE_REQUIRED, 'Path to the "after" directory (git repo)')
            ->addOption('compact', null, InputOption::VALUE_NONE, 'Single combined diff with commit messages in header');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fqcnOpt = $input->getOption('fqcn');
        $beforeOpt = $input->getOption('before');
        $afterOpt = $input->getOption('after');
        $compact = (bool) $input->getOption('compact');

        $fqcn = is_string($fqcnOpt) ? $fqcnOpt : null;
        $beforeDir = is_string($beforeOpt) ? $beforeOpt : null;
        $afterDir = is_string($afterOpt) ? $afterOpt : null;

        if ($fqcn === null || $beforeDir === null || $afterDir === null) {
            $output->writeln('<error>Options --fqcn, --before, and --after are required.</error>');
            return Command::FAILURE;
        }

        $analyzer = new DivergenceAnalyzer();

        $beforeFile = $analyzer->findFileForFqcn($beforeDir, $fqcn);
        $afterFile = $analyzer->findFileForFqcn($afterDir, $fqcn);

        if ($afterFile === null) {
            $output->writeln(sprintf('<error>Could not find file for %s in the "after" directory.</error>', $fqcn));
            return Command::FAILURE;
        }

        $afterRepoRoot = $analyzer->getRepoRoot($afterDir);
        if ($afterRepoRoot === null) {
            $output->writeln('<error>The "after" directory is not inside a git repository.</error>');
            return Command::FAILURE;
        }

        $afterRelPath = $analyzer->getRelativePath($afterRepoRoot, $afterFile);

        // Find divergence
        if ($beforeFile !== null) {
            $beforeRepoRoot = $analyzer->getRepoRoot($beforeDir);
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
            return Command::SUCCESS;
        }

        if ($compact) {
            return $this->outputCompact($output, $analyzer, $afterRepoRoot, $afterRelPath, $common, $divergent, $hasUncommitted);
        }

        return $this->outputFormatPatch($output, $analyzer, $afterRepoRoot, $afterRelPath, $divergent, $uncommitted);
    }

    /**
     * @param CommitInfo[] $divergent
     */
    private function outputFormatPatch(
        OutputInterface $output,
        DivergenceAnalyzer $analyzer,
        string $repoRoot,
        string $relPath,
        array $divergent,
        ?string $uncommitted,
    ): int {
        $totalPatches = count($divergent) + ($uncommitted !== null ? 1 : 0);

        // Batch-fetch all patches in a single git call
        $hashes = array_map(fn (CommitInfo $ci) => $ci->hash, $divergent);
        $patches = $analyzer->getCommitPatches($repoRoot, $relPath, $hashes);

        $patchNum = 0;
        foreach ($divergent as $commit) {
            $patchNum++;
            $this->writeFormatPatch($output, $patches[$commit->hash] ?? null, $commit, $patchNum, $totalPatches);
        }

        if ($uncommitted !== null) {
            $patchNum++;
            $this->writeUncommittedPatch($output, $uncommitted, $patchNum, $totalPatches);
        }

        return Command::SUCCESS;
    }

    /**
     * @param CommitInfo[] $divergent
     */
    private function outputCompact(
        OutputInterface $output,
        DivergenceAnalyzer $analyzer,
        string $repoRoot,
        string $relPath,
        ?CommitInfo $common,
        array $divergent,
        bool $hasUncommitted,
    ): int {
        $wr = fn (string $line) => $output->writeln($line, OutputInterface::OUTPUT_RAW);

        $last = $divergent !== [] ? end($divergent) : null;
        $hash = $last !== null ? $last->hash : str_repeat('0', 40);
        $author = $last !== null ? $last->author : '(working tree)';
        $email = $last !== null ? $last->email : 'noreply';
        $date = $last !== null ? $last->date : '(not committed)';

        $wr(sprintf('From %s Mon Sep 17 00:00:00 2001', $hash));
        $wr(sprintf('From: %s <%s>', $author, $email));
        $wr(sprintf('Date: %s', $date));
        $wr('Subject: [PATCH] Combined changes');
        $wr('');

        foreach ($divergent as $commit) {
            $wr(sprintf('  %s %s', $commit->shortHash, $commit->subject));
        }
        if ($hasUncommitted) {
            $wr('  (uncommitted changes)');
        }

        $wr('');
        $wr('---');

        $diff = $analyzer->getDiffSince($repoRoot, $relPath, $common?->hash);
        if ($diff !== null) {
            $output->write($diff, false, OutputInterface::OUTPUT_RAW);
            if (!str_ends_with($diff, "\n")) {
                $wr('');
            }
        }

        $wr('');

        return Command::SUCCESS;
    }

    private function writeFormatPatch(
        OutputInterface $output,
        ?string $patch,
        CommitInfo $commit,
        int $patchNum,
        int $totalPatches,
    ): void {
        $wr = fn (string $line) => $output->writeln($line, OutputInterface::OUTPUT_RAW);

        $wr(sprintf('From %s Mon Sep 17 00:00:00 2001', $commit->hash));
        $wr(sprintf('From: %s <%s>', $commit->author, $commit->email));
        $wr(sprintf('Date: %s', $commit->date));
        $wr(sprintf('Subject: [PATCH %d/%d] %s', $patchNum, $totalPatches, $commit->subject));
        $wr('');

        $bodyLines = explode("\n", $commit->body);
        if (count($bodyLines) > 1) {
            foreach (array_slice($bodyLines, 1) as $line) {
                $wr('    ' . $line);
            }
            $wr('');
        }

        $wr('---');

        if ($patch !== null && trim($patch) !== '') {
            $output->write($patch, false, OutputInterface::OUTPUT_RAW);
            if (!str_ends_with($patch, "\n")) {
                $wr('');
            }
        }

        $wr('');
    }

    private function writeUncommittedPatch(
        OutputInterface $output,
        string $patch,
        int $patchNum,
        int $totalPatches,
    ): void {
        $wr = fn (string $line) => $output->writeln($line, OutputInterface::OUTPUT_RAW);

        $wr(sprintf('From %s Mon Sep 17 00:00:00 2001', str_repeat('0', 40)));
        $wr('From: (working tree)');
        $wr('Date: (not committed)');
        $wr(sprintf('Subject: [PATCH %d/%d] Uncommitted changes', $patchNum, $totalPatches));
        $wr('');
        $wr('---');
        $output->write($patch, false, OutputInterface::OUTPUT_RAW);
        if (!str_ends_with($patch, "\n")) {
            $wr('');
        }
        $wr('');
    }
}
