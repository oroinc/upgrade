<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Git;

use Oro\UpgradeToolkit\Semadiff\FqcnPathMap;

final class DivergenceAnalyzer
{
    private const FIELD_SEP = "\x1f";
    private const RECORD_SEP = "\x1e";

    /**
     * Find a PHP file matching the FQCN within a directory.
     */
    public function findFileForFqcn(string $dir, string $fqcn, ?FqcnPathMap $pathMap = null): ?string
    {
        $relativePath = str_replace('\\', '/', $fqcn) . '.php';

        foreach (['', 'src/'] as $prefix) {
            $candidate = $dir . '/' . $prefix . $relativePath;
            if (is_file($candidate)) {
                $resolved = realpath($candidate);
                return $resolved !== false ? $resolved : $candidate;
            }
        }

        return $pathMap?->get($fqcn);
    }

    /**
     * Get the git repository root for a directory.
     */
    public function getRepoRoot(string $dir): ?string
    {
        $result = $this->git($dir, 'rev-parse --show-toplevel');

        return $result !== null ? trim($result) : null;
    }

    /**
     * Get the file path relative to the repo root.
     */
    public function getRelativePath(string $repoRoot, string $absolutePath): string
    {
        $resolvedRoot = realpath($repoRoot);
        $root = rtrim($resolvedRoot !== false ? $resolvedRoot : $repoRoot, '/') . '/';
        $resolvedAbs = realpath($absolutePath);
        $abs = $resolvedAbs !== false ? $resolvedAbs : $absolutePath;

        if (str_starts_with($abs, $root)) {
            return substr($abs, strlen($root));
        }

        return $absolutePath;
    }

    /**
     * Get commits that touched a file, ordered oldest first.
     *
     * @return CommitInfo[]
     */
    public function getFileCommits(string $repoRoot, string $relPath): array
    {
        $fs = self::FIELD_SEP;
        $rs = self::RECORD_SEP;
        $format = "%H{$fs}%h{$fs}%an{$fs}%ae{$fs}%aD{$fs}%B{$rs}";

        $raw = $this->git(
            $repoRoot,
            'log --format=' . escapeshellarg($format) . ' --reverse -- ' . escapeshellarg($relPath),
        );

        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $commits = [];
        foreach (explode($rs, $raw) as $record) {
            $record = trim($record);
            if ($record === '') {
                continue;
            }
            $parts = explode($fs, $record, 6);
            if (count($parts) === 6) {
                $commits[] = new CommitInfo(
                    hash: $parts[0],
                    shortHash: $parts[1],
                    author: $parts[2],
                    email: $parts[3],
                    date: $parts[4],
                    body: trim($parts[5]),
                );
            }
        }

        return $commits;
    }

    /**
     * Get file content at a specific commit.
     */
    public function getContentAtCommit(string $repoRoot, string $relPath, string $hash): ?string
    {
        return $this->git(
            $repoRoot,
            'show ' . escapeshellarg($hash) . ':' . escapeshellarg($relPath),
        );
    }

    /**
     * Get the unified diff for a single commit, filtered to one file.
     */
    public function getCommitPatch(string $repoRoot, string $relPath, string $hash): ?string
    {
        return $this->git(
            $repoRoot,
            'diff-tree --root -p --no-color --no-commit-id '
            . escapeshellarg($hash) . ' -- ' . escapeshellarg($relPath),
        );
    }

    /**
     * Get the unified diff for uncommitted changes (staged + unstaged).
     */
    public function getWorkingTreePatch(string $repoRoot, string $relPath): ?string
    {
        $diff = $this->git(
            $repoRoot,
            'diff -p --no-color HEAD -- ' . escapeshellarg($relPath),
        );

        if ($diff === null || trim($diff) === '') {
            return null;
        }

        return $diff;
    }

    /**
     * Get a single combined diff from a commit to the current working tree.
     */
    public function getDiffSince(string $repoRoot, string $relPath, ?string $hash): ?string
    {
        if ($hash !== null) {
            $diff = $this->git(
                $repoRoot,
                'diff -p --no-color ' . escapeshellarg($hash) . ' -- ' . escapeshellarg($relPath),
            );
        } else {
            $diff = $this->git(
                $repoRoot,
                'diff -p --no-color --no-index /dev/null ' . escapeshellarg($relPath),
            );
        }

        if ($diff === null || trim($diff) === '') {
            return null;
        }

        return $diff;
    }

    /**
     * Find the divergence point between two repos for a given file.
     *
     * Collects content hashes from all "before" commits, then walks "after"
     * commits from newest to oldest. The first commit in "after" whose content
     * matches any "before" state is the common ancestor.
     *
     * @return array{common: ?CommitInfo, commits: CommitInfo[]}
     */
    public function findDivergence(
        string $beforeRepoRoot,
        string $beforeRelPath,
        string $afterRepoRoot,
        string $afterRelPath,
    ): array {
        $beforeHashes = $this->buildContentHashSet($beforeRepoRoot, $beforeRelPath);

        // Also include current working-tree content
        $currentPath = $beforeRepoRoot . '/' . $beforeRelPath;
        if (is_file($currentPath)) {
            $content = file_get_contents($currentPath);
            if ($content !== false) {
                $beforeHashes[md5($content)] = true;
            }
        }

        $afterCommits = $this->getFileCommits($afterRepoRoot, $afterRelPath);

        if ($afterCommits === []) {
            return ['common' => null, 'commits' => []];
        }

        // Batch-fetch all "after" contents in a single git call
        $afterHashList = array_map(fn (CommitInfo $ci) => $ci->hash, $afterCommits);
        $afterContents = $this->getContentsAtCommits($afterRepoRoot, $afterRelPath, $afterHashList);

        // Walk from newest to oldest — first match is the divergence point
        $commonAncestor = null;
        $commonIndex = null;

        foreach (array_reverse($afterCommits) as $i => $commit) {
            $content = $afterContents[$commit->hash] ?? null;
            if ($content !== null && isset($beforeHashes[md5($content)])) {
                $commonAncestor = $commit;
                $commonIndex = count($afterCommits) - 1 - $i;
                break;
            }
        }

        $divergent = $commonIndex !== null
            ? array_slice($afterCommits, $commonIndex + 1)
            : $afterCommits;

        return [
            'common' => $commonAncestor,
            'commits' => $divergent,
        ];
    }

    /**
     * Batch-fetch file contents at multiple commits in a single git call.
     *
     * @param string[] $commitHashes
     * @return array<string, ?string> hash => content
     */
    public function getContentsAtCommits(string $repoRoot, string $relPath, array $commitHashes): array
    {
        if ($commitHashes === []) {
            return [];
        }

        $input = implode("\n", array_map(
            fn (string $ch) => $ch . ':' . $relPath,
            $commitHashes,
        ));

        $cmd = sprintf(
            'printf %%s %s | git -C %s cat-file --batch 2>/dev/null',
            escapeshellarg($input),
            escapeshellarg($repoRoot),
        );

        $raw = shell_exec($cmd);
        if (!is_string($raw)) {
            return array_fill_keys($commitHashes, null);
        }

        $results = [];
        $offset = 0;
        $len = strlen($raw);

        foreach ($commitHashes as $hash) {
            if ($offset >= $len) {
                $results[$hash] = null;
                continue;
            }

            $nlPos = strpos($raw, "\n", $offset);
            if ($nlPos === false) {
                $results[$hash] = null;
                continue;
            }

            $header = substr($raw, $offset, $nlPos - $offset);
            $offset = $nlPos + 1;

            if (str_ends_with($header, ' missing')) {
                $results[$hash] = null;
                continue;
            }

            $parts = explode(' ', $header);
            $size = (int) end($parts);

            $results[$hash] = substr($raw, $offset, $size);
            $offset += $size + 1; // +1 for trailing \n
        }

        return $results;
    }

    /**
     * Batch-fetch patches for multiple commits in a single git call.
     *
     * @param string[] $commitHashes
     * @return array<string, ?string> hash => patch
     */
    public function getCommitPatches(string $repoRoot, string $relPath, array $commitHashes): array
    {
        if ($commitHashes === []) {
            return [];
        }

        $input = implode("\n", $commitHashes) . "\n";

        $cmd = sprintf(
            'printf %%s %s | git -C %s diff-tree --stdin --root -p --no-color -- %s 2>/dev/null',
            escapeshellarg($input),
            escapeshellarg($repoRoot),
            escapeshellarg($relPath),
        );

        $raw = shell_exec($cmd);
        if (!is_string($raw) || $raw === '') {
            return array_fill_keys($commitHashes, null);
        }

        $hashSet = array_flip($commitHashes);
        $results = array_fill_keys($commitHashes, null);
        $currentHash = null;
        $currentLines = [];

        foreach (explode("\n", $raw) as $line) {
            if (preg_match('/^([0-9a-f]{40})/', $line, $match) === 1 && isset($hashSet[$match[1]])) {
                if ($currentHash !== null) {
                    $patch = implode("\n", $currentLines);
                    $results[$currentHash] = trim($patch) !== '' ? $patch : null;
                }
                $currentHash = $match[1];
                $currentLines = [];
            } else {
                $currentLines[] = $line;
            }
        }

        if ($currentHash !== null) {
            $patch = implode("\n", $currentLines);
            $results[$currentHash] = trim($patch) !== '' ? $patch : null;
        }

        return $results;
    }

    /**
     * @return array<string, bool>
     */
    private function buildContentHashSet(string $repoRoot, string $relPath): array
    {
        $commits = $this->getFileCommits($repoRoot, $relPath);
        if ($commits === []) {
            return [];
        }

        $hashes = array_map(fn (CommitInfo $ci) => $ci->hash, $commits);
        $contents = $this->getContentsAtCommits($repoRoot, $relPath, $hashes);

        $result = [];
        foreach ($contents as $content) {
            if ($content !== null) {
                $result[md5($content)] = true;
            }
        }

        return $result;
    }

    private function git(string $repoDir, string $args): ?string
    {
        $cmd = sprintf('git -C %s %s 2>/dev/null', escapeshellarg($repoDir), $args);
        $result = shell_exec($cmd);

        return is_string($result) ? $result : null;
    }
}
