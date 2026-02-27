<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit;

use Oro\UpgradeToolkit\Semadiff\FileCollector;
use Oro\UpgradeToolkit\Semadiff\FqcnPathMap;
use Oro\UpgradeToolkit\Tests\Semadiff\Support\TempDirTrait;
use PHPUnit\Framework\TestCase;

final class FileCollectorTest extends TestCase
{
    use TempDirTrait;

    protected function setUp(): void
    {
        $this->setUpTempDir();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDir();
    }

    public function testUniqueBasenameMatchProducesMovedEntry(): void
    {
        // before: old/Foo.php, deleted after move
        // after:  new/Foo.php, added after move
        // Common: Common.php (paired)
        $this->createFile('before/Common.php', '<?php class Common {}');
        $this->createFile('before/old/Foo.php', '<?php class Foo {}');
        $this->createFile('after/Common.php', '<?php class Common {}');
        $this->createFile('after/new/Foo.php', '<?php class Foo {}');

        $collector = new FileCollector();
        $result = $collector->collect($this->tmpDir . '/before', $this->tmpDir . '/after');

        $this->assertSame(['Common.php'], $result['paired']);
        $this->assertSame([], $result['new']);
        $this->assertSame([], $result['deleted']);
        $this->assertCount(1, $result['moved']);
        $this->assertSame('old/Foo.php', $result['moved'][0]['before']);
        $this->assertSame('new/Foo.php', $result['moved'][0]['after']);
    }

    public function testAmbiguousBasenameStaysAsNewAndDeleted(): void
    {
        // Two new files with same basename — ambiguous, no move detected
        $this->createFile('before/old/Bar.php', '<?php class Bar {}');
        $this->createFile('after/new1/Bar.php', '<?php class Bar1 {}');
        $this->createFile('after/new2/Bar.php', '<?php class Bar2 {}');

        $collector = new FileCollector();
        $result = $collector->collect($this->tmpDir . '/before', $this->tmpDir . '/after');

        $this->assertSame([], $result['paired']);
        $this->assertCount(2, $result['new']);
        $this->assertSame(['old/Bar.php'], $result['deleted']);
        $this->assertSame([], $result['moved']);
    }

    public function testAmbiguousDeletedBasenameStaysAsNewAndDeleted(): void
    {
        // Two deleted files with same basename — ambiguous, no move detected
        $this->createFile('before/old1/Baz.php', '<?php class Baz1 {}');
        $this->createFile('before/old2/Baz.php', '<?php class Baz2 {}');
        $this->createFile('after/new/Baz.php', '<?php class Baz {}');

        $collector = new FileCollector();
        $result = $collector->collect($this->tmpDir . '/before', $this->tmpDir . '/after');

        $this->assertSame([], $result['paired']);
        $this->assertSame(['new/Baz.php'], $result['new']);
        $this->assertCount(2, $result['deleted']);
        $this->assertSame([], $result['moved']);
    }

    public function testNoMovesWhenAllFilesPaired(): void
    {
        $this->createFile('before/Foo.php', '<?php class Foo {}');
        $this->createFile('after/Foo.php', '<?php class Foo {}');

        $collector = new FileCollector();
        $result = $collector->collect($this->tmpDir . '/before', $this->tmpDir . '/after');

        $this->assertSame(['Foo.php'], $result['paired']);
        $this->assertSame([], $result['new']);
        $this->assertSame([], $result['deleted']);
        $this->assertSame([], $result['moved']);
    }

    public function testMultipleMovesDetected(): void
    {
        $this->createFile('before/old/A.php', '<?php class A {}');
        $this->createFile('before/old/B.php', '<?php class B {}');
        $this->createFile('after/new/A.php', '<?php class A {}');
        $this->createFile('after/new/B.php', '<?php class B {}');

        $collector = new FileCollector();
        $result = $collector->collect($this->tmpDir . '/before', $this->tmpDir . '/after');

        $this->assertSame([], $result['paired']);
        $this->assertSame([], $result['new']);
        $this->assertSame([], $result['deleted']);
        $this->assertCount(2, $result['moved']);

        $movedAfters = array_column($result['moved'], 'after');
        sort($movedAfters);
        $this->assertSame(['new/A.php', 'new/B.php'], $movedAfters);
    }

    public function testFqcnBasedMoveDetectionWhenBasenameIsAmbiguous(): void
    {
        // Two deleted files with same basename "Connection.php" but different FQCNs
        $this->createFile('before/lib/Doctrine/DBAL/Connection.php', <<<'PHP'
            <?php
            namespace Doctrine\DBAL;
            class Connection {}
            PHP);
        $this->createFile('before/lib/Doctrine/ORM/Connection.php', <<<'PHP'
            <?php
            namespace Doctrine\ORM;
            class Connection {}
            PHP);

        // Matching new files at different paths
        $this->createFile('after/src/Connection.php', <<<'PHP'
            <?php
            namespace Doctrine\DBAL;
            class Connection {}
            PHP);
        $this->createFile('after/src/ORM/Connection.php', <<<'PHP'
            <?php
            namespace Doctrine\ORM;
            class Connection {}
            PHP);

        $collector = new FileCollector();
        $result = $collector->collect($this->tmpDir . '/before', $this->tmpDir . '/after');

        $this->assertSame([], $result['paired']);
        $this->assertSame([], $result['new']);
        $this->assertSame([], $result['deleted']);
        $this->assertCount(2, $result['moved']);

        $movedMap = [];
        foreach ($result['moved'] as $move) {
            $movedMap[$move['before']] = $move['after'];
        }
        $this->assertSame('src/Connection.php', $movedMap['lib/Doctrine/DBAL/Connection.php']);
        $this->assertSame('src/ORM/Connection.php', $movedMap['lib/Doctrine/ORM/Connection.php']);
    }

    public function testCollectReturnsPathMaps(): void
    {
        $this->createFile('before/Vendor/Foo.php', "<?php\nnamespace Vendor;\nclass Foo {}");
        $this->createFile('after/Vendor/Foo.php', "<?php\nnamespace Vendor;\nclass Foo {}");
        $this->createFile('after/Vendor/Bar.php', "<?php\nnamespace Vendor;\nclass Bar {}");

        $collector = new FileCollector();
        $result = $collector->collect($this->tmpDir . '/before', $this->tmpDir . '/after');

        $this->assertInstanceOf(FqcnPathMap::class, $result['beforePaths']);
        $this->assertInstanceOf(FqcnPathMap::class, $result['afterPaths']);
        $this->assertNotNull($result['beforePaths']->get('Vendor\\Foo'));
        $this->assertNotNull($result['afterPaths']->get('Vendor\\Foo'));
        $this->assertNotNull($result['afterPaths']->get('Vendor\\Bar'));
        $this->assertNull($result['beforePaths']->get('Vendor\\Bar'));
    }

    public function testFqcnMoveNotDetectedWhenFqcnIsAmbiguous(): void
    {
        // Same FQCN in two deleted files — ambiguous, should stay as deleted/new
        $this->createFile('before/v1/Foo.php', <<<'PHP'
            <?php
            namespace App;
            class Foo {}
            PHP);
        $this->createFile('before/v2/Foo.php', <<<'PHP'
            <?php
            namespace App;
            class Foo {}
            PHP);

        $this->createFile('after/new/Foo.php', <<<'PHP'
            <?php
            namespace App;
            class Foo {}
            PHP);

        $collector = new FileCollector();
        $result = $collector->collect($this->tmpDir . '/before', $this->tmpDir . '/after');

        $this->assertSame([], $result['paired']);
        $this->assertSame(['new/Foo.php'], $result['new']);
        $this->assertCount(2, $result['deleted']);
        $this->assertSame([], $result['moved']);
    }

}
