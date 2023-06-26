<?php

declare(strict_types=1);

namespace Oro\Tests\Rector\GenerateTopicClassesRector;

use Nette\Utils\FileSystem;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class GenerateTopicClassesRectorTest extends AbstractRectorTestCase
{
    public function testClassInterfaceAndTraitSplit(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/fixture.php.inc');

        $this->assertFileWasAdded(
            __DIR__ . '/Fixture/SendSmsTopic.php',
            FileSystem::read(__DIR__ . '/Expected/Async/SendSmsTopic.php')
        );

        $this->assertFileWasAdded(
            __DIR__ . '/Fixture/SendEmailTopic.php',
            FileSystem::read(__DIR__ . '/Expected/Async/SendEmailTopic.php')
        );

        $isFileRemoved = $this->removedAndAddedFilesCollector->isFileRemoved(
            __DIR__ . '/Fixture/fixture.php'
        );
        $this->assertTrue($isFileRemoved);
    }

    public function testKeepFileWithDifferentNamespace(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/different_namespace.php.inc');
    }

    public function testKeepFileWithDifferentName(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/different_name.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
