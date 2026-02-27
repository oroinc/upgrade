<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Git;

use Oro\UpgradeToolkit\Semadiff\FqcnPathMap;
use Oro\UpgradeToolkit\Semadiff\Git\DivergenceAnalyzer;
use PHPUnit\Framework\TestCase;

final class DivergenceAnalyzerTest extends TestCase
{
    private DivergenceAnalyzer $analyzer;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->analyzer = new DivergenceAnalyzer();
        $this->tmpDir = sys_get_temp_dir() . '/semadiff_git_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    // --- getRelativePath ---

    public function testGetRelativePath(): void
    {
        $this->assertSame(
            'src/Foo/Bar.php',
            $this->analyzer->getRelativePath('/project', '/project/src/Foo/Bar.php'),
        );
    }

    public function testGetRelativePathTrailingSlash(): void
    {
        $this->assertSame(
            'src/Foo/Bar.php',
            $this->analyzer->getRelativePath('/project/', '/project/src/Foo/Bar.php'),
        );
    }

    public function testGetRelativePathNotPrefix(): void
    {
        // When the absolute path is not under repoRoot, returns as-is
        $this->assertSame(
            '/other/path/File.php',
            $this->analyzer->getRelativePath('/project', '/other/path/File.php'),
        );
    }

    // --- findFileForFqcn ---

    public function testFindFileDirectPath(): void
    {
        $dir = $this->tmpDir . '/Vendor/Lib';
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/MyClass.php', "<?php\nnamespace Vendor\\Lib;\nclass MyClass {}\n");

        $result = $this->analyzer->findFileForFqcn($this->tmpDir, 'Vendor\\Lib\\MyClass');
        $this->assertNotNull($result);
        $this->assertStringEndsWith('Vendor/Lib/MyClass.php', $result);
    }

    public function testFindFileWithSrcPrefix(): void
    {
        $dir = $this->tmpDir . '/src/App/Service';
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/Handler.php', "<?php\nnamespace App\\Service;\nclass Handler {}\n");

        $result = $this->analyzer->findFileForFqcn($this->tmpDir, 'App\\Service\\Handler');
        $this->assertNotNull($result);
        $this->assertStringEndsWith('src/App/Service/Handler.php', $result);
    }

    public function testFindFileWithoutPathMapReturnsNullForNonStandard(): void
    {
        // Put file in a non-standard location — without pathMap, direct lookups fail
        $dir = $this->tmpDir . '/bundles/custom/Vendor/Lib';
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/MyClass.php', "<?php\nnamespace Vendor\\Lib;\nclass MyClass {}\n");

        $result = $this->analyzer->findFileForFqcn($this->tmpDir, 'Vendor\\Lib\\MyClass');
        $this->assertNull($result);
    }

    public function testFindFileWithPathMapFindsNonStandard(): void
    {
        // With pathMap, non-standard locations are found via O(1) lookup
        $dir = $this->tmpDir . '/bundles/custom/Vendor/Lib';
        mkdir($dir, 0755, true);
        $filePath = $dir . '/MyClass.php';
        file_put_contents($filePath, "<?php\nnamespace Vendor\\Lib;\nclass MyClass {}\n");

        $pathMap = new FqcnPathMap();
        $pathMap->set('Vendor\\Lib\\MyClass', $filePath);

        $result = $this->analyzer->findFileForFqcn($this->tmpDir, 'Vendor\\Lib\\MyClass', $pathMap);
        $this->assertSame($filePath, $result);
    }

    public function testFindFileNotFound(): void
    {
        $this->assertNull($this->analyzer->findFileForFqcn($this->tmpDir, 'Nonexistent\\Class'));
    }

    public function testFindFileWrongNamespace(): void
    {
        $dir = $this->tmpDir . '/Wrong/Path';
        mkdir($dir, 0755, true);
        // File named MyClass.php but with a different namespace
        file_put_contents($dir . '/MyClass.php', "<?php\nnamespace Other\\Namespace;\nclass MyClass {}\n");

        // Direct path Vendor/Lib/MyClass.php doesn't exist, no pathMap — returns null
        $this->assertNull($this->analyzer->findFileForFqcn($this->tmpDir, 'Vendor\\Lib\\MyClass'));
    }

    // --- Git-based tests (require real git repos) ---

    public function testGetFileCommitsWithRealRepo(): void
    {
        $repo = $this->createGitRepo('repo');
        $this->commitFile($repo, 'src/Foo.php', "<?php\nclass Foo {}\n", 'initial');
        $this->commitFile($repo, 'src/Foo.php', "<?php\nclass Foo { public function bar() {} }\n", 'add bar');

        $commits = $this->analyzer->getFileCommits($repo, 'src/Foo.php');

        $this->assertCount(2, $commits);
        $this->assertSame('initial', $commits[0]->subject);
        $this->assertSame('add bar', $commits[1]->subject);
    }

    public function testGetContentAtCommit(): void
    {
        $repo = $this->createGitRepo('repo');
        $this->commitFile($repo, 'src/Foo.php', "v1\n", 'first');
        $this->commitFile($repo, 'src/Foo.php', "v2\n", 'second');

        $commits = $this->analyzer->getFileCommits($repo, 'src/Foo.php');

        $this->assertSame("v1\n", $this->analyzer->getContentAtCommit($repo, 'src/Foo.php', $commits[0]->hash));
        $this->assertSame("v2\n", $this->analyzer->getContentAtCommit($repo, 'src/Foo.php', $commits[1]->hash));
    }

    public function testGetCommitStatAndPatch(): void
    {
        $repo = $this->createGitRepo('repo');
        $this->commitFile($repo, 'src/Foo.php', "line1\n", 'first');
        $this->commitFile($repo, 'src/Foo.php', "line1\nline2\n", 'add line');

        $commits = $this->analyzer->getFileCommits($repo, 'src/Foo.php');
        $body = $this->analyzer->getCommitPatch($repo, 'src/Foo.php', $commits[1]->hash);

        $this->assertNotNull($body);
        $this->assertStringContainsString('+line2', $body);
        // Should include stat
        $this->assertStringContainsString('diff --git', $body);
    }

    public function testGetWorkingTreeStatAndPatchNoChanges(): void
    {
        $repo = $this->createGitRepo('repo');
        $this->commitFile($repo, 'src/Foo.php', "content\n", 'init');

        $this->assertNull($this->analyzer->getWorkingTreePatch($repo, 'src/Foo.php'));
    }

    public function testGetWorkingTreeStatAndPatchWithChanges(): void
    {
        $repo = $this->createGitRepo('repo');
        $this->commitFile($repo, 'src/Foo.php', "old\n", 'init');
        file_put_contents($repo . '/src/Foo.php', "new\n");

        $body = $this->analyzer->getWorkingTreePatch($repo, 'src/Foo.php');
        $this->assertNotNull($body);
        $this->assertStringContainsString('-old', $body);
        $this->assertStringContainsString('+new', $body);
        $this->assertStringContainsString('diff --git', $body);
    }

    public function testCommitInfoHasEmail(): void
    {
        $repo = $this->createGitRepo('repo');
        $this->commitFile($repo, 'src/Foo.php', "x\n", 'init');

        $commits = $this->analyzer->getFileCommits($repo, 'src/Foo.php');
        $this->assertSame('test@test.com', $commits[0]->email);
        $this->assertSame('Test', $commits[0]->author);
    }

    public function testFindDivergenceWithCommonHistory(): void
    {
        $before = $this->createGitRepo('before');
        $this->commitFile($before, 'src/Foo.php', "v1\n", 'shared commit 1');
        $this->commitFile($before, 'src/Foo.php', "v2\n", 'shared commit 2');

        $after = $this->createGitRepo('after');
        $this->commitFile($after, 'src/Foo.php', "v1\n", 'import v1');
        $this->commitFile($after, 'src/Foo.php', "v2\n", 'import v2');
        $this->commitFile($after, 'src/Foo.php', "v2-custom\n", 'custom change 1');
        $this->commitFile($after, 'src/Foo.php', "v2-custom-2\n", 'custom change 2');

        $result = $this->analyzer->findDivergence($before, 'src/Foo.php', $after, 'src/Foo.php');

        $this->assertNotNull($result['common']);
        $this->assertSame('import v2', $result['common']->subject);
        $this->assertCount(2, $result['commits']);
        $this->assertSame('custom change 1', $result['commits'][0]->subject);
        $this->assertSame('custom change 2', $result['commits'][1]->subject);
    }

    public function testFindDivergenceNoCommonHistory(): void
    {
        $before = $this->createGitRepo('before');
        $this->commitFile($before, 'src/Foo.php', "upstream\n", 'upstream');

        $after = $this->createGitRepo('after');
        $this->commitFile($after, 'src/Foo.php', "custom\n", 'custom');

        $result = $this->analyzer->findDivergence($before, 'src/Foo.php', $after, 'src/Foo.php');

        $this->assertNull($result['common']);
        $this->assertCount(1, $result['commits']);
    }

    public function testFindDivergenceIdenticalHistory(): void
    {
        $before = $this->createGitRepo('before');
        $this->commitFile($before, 'src/Foo.php', "same\n", 'commit');

        $after = $this->createGitRepo('after');
        $this->commitFile($after, 'src/Foo.php', "same\n", 'commit');

        $result = $this->analyzer->findDivergence($before, 'src/Foo.php', $after, 'src/Foo.php');

        $this->assertNotNull($result['common']);
        $this->assertCount(0, $result['commits']);
    }

    public function testFindDivergenceMatchesWorkingTreeContent(): void
    {
        // "before" has uncommitted changes that match "after" committed content
        $before = $this->createGitRepo('before');
        $this->commitFile($before, 'src/Foo.php', "v1\n", 'old');
        // Overwrite working tree with v2 (not committed)
        file_put_contents($before . '/src/Foo.php', "v2\n");

        $after = $this->createGitRepo('after');
        $this->commitFile($after, 'src/Foo.php', "v2\n", 'matching');
        $this->commitFile($after, 'src/Foo.php', "v3\n", 'divergent');

        $result = $this->analyzer->findDivergence($before, 'src/Foo.php', $after, 'src/Foo.php');

        $this->assertNotNull($result['common']);
        $this->assertSame('matching', $result['common']->subject);
        $this->assertCount(1, $result['commits']);
        $this->assertSame('divergent', $result['commits'][0]->subject);
    }

    public function testGetRepoRoot(): void
    {
        $repo = $this->createGitRepo('repo');
        $result = $this->analyzer->getRepoRoot($repo);
        $this->assertNotNull($result);
        $this->assertSame(realpath($repo), realpath($result));
    }

    public function testGetRepoRootNonGitDir(): void
    {
        $this->assertNull($this->analyzer->getRepoRoot($this->tmpDir));
    }

    // --- Batch methods ---

    public function testGetContentsAtCommitsBatch(): void
    {
        $repo = $this->createGitRepo('repo');
        $this->commitFile($repo, 'src/Foo.php', "v1\n", 'first');
        $this->commitFile($repo, 'src/Foo.php', "v2\n", 'second');
        $this->commitFile($repo, 'src/Foo.php', "v3\n", 'third');

        $commits = $this->analyzer->getFileCommits($repo, 'src/Foo.php');
        $hashes = array_map(fn ($c) => $c->hash, $commits);

        $contents = $this->analyzer->getContentsAtCommits($repo, 'src/Foo.php', $hashes);

        $this->assertCount(3, $contents);
        $this->assertSame("v1\n", $contents[$hashes[0]]);
        $this->assertSame("v2\n", $contents[$hashes[1]]);
        $this->assertSame("v3\n", $contents[$hashes[2]]);
    }

    public function testGetCommitPatchesBatch(): void
    {
        $repo = $this->createGitRepo('repo');
        $this->commitFile($repo, 'src/Foo.php', "line1\n", 'first');
        $this->commitFile($repo, 'src/Foo.php', "line1\nline2\n", 'add line2');
        $this->commitFile($repo, 'src/Foo.php', "line1\nline2\nline3\n", 'add line3');

        $commits = $this->analyzer->getFileCommits($repo, 'src/Foo.php');
        $hashes = array_map(fn ($c) => $c->hash, $commits);

        $patches = $this->analyzer->getCommitPatches($repo, 'src/Foo.php', $hashes);

        $this->assertCount(3, $patches);
        $this->assertStringContainsString('+line1', $patches[$hashes[0]]);
        $this->assertStringContainsString('+line2', $patches[$hashes[1]]);
        $this->assertStringContainsString('+line3', $patches[$hashes[2]]);
    }

    public function testGetContentsAtCommitsEmpty(): void
    {
        $this->assertSame([], $this->analyzer->getContentsAtCommits('/tmp', 'any', []));
    }

    public function testGetCommitPatchesEmpty(): void
    {
        $this->assertSame([], $this->analyzer->getCommitPatches('/tmp', 'any', []));
    }

    // --- Helpers ---

    private function createGitRepo(string $name): string
    {
        $dir = $this->tmpDir . '/' . $name;
        mkdir($dir, 0755, true);
        shell_exec(sprintf('git -C %s init -b main 2>/dev/null', escapeshellarg($dir)));
        shell_exec(sprintf('git -C %s config user.email "test@test.com" 2>/dev/null', escapeshellarg($dir)));
        shell_exec(sprintf('git -C %s config user.name "Test" 2>/dev/null', escapeshellarg($dir)));

        return $dir;
    }

    private function commitFile(string $repo, string $relPath, string $content, string $message): void
    {
        $absPath = $repo . '/' . $relPath;
        $dir = dirname($absPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($absPath, $content);
        shell_exec(sprintf('git -C %s add %s 2>/dev/null', escapeshellarg($repo), escapeshellarg($relPath)));
        shell_exec(sprintf('git -C %s commit -m %s 2>/dev/null', escapeshellarg($repo), escapeshellarg($message)));
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
