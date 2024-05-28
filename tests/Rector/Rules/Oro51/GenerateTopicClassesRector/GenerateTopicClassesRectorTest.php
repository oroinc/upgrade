<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\GenerateTopicClassesRector;

use Nette\Utils\FileSystem;
use Oro\UpgradeToolkit\Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class GenerateTopicClassesRectorTest extends AbstractRectorTestCase
{
    protected const ACTUAL_TOPIC_FILES = [
        'SendSmsTopic' => __DIR__ . '/Fixture/SendSmsTopic.php',
        'SendEmailTopic' => __DIR__ . '/Fixture/SendEmailTopic.php',
    ];

    protected const EXPECTED_TOPIC_FILES = [
        'SendSmsTopic' => __DIR__ . '/Expected/Async/SendSmsTopic.php',
        'SendEmailTopic' => __DIR__ . '/Expected/Async/SendEmailTopic.php',
    ];

    protected function tearDown(): void
    {
        // Clear generated files after the test is completed
        foreach (self::ACTUAL_TOPIC_FILES as $key => $path) {
            FileSystem::delete($path);
        }

        parent::tearDown();
    }

    public function testClassInterfaceAndTraitSplit(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/fixture.php.inc');

        $this->assertFileWasAdded(
            self::ACTUAL_TOPIC_FILES['SendSmsTopic'],
            FileSystem::read(self::EXPECTED_TOPIC_FILES['SendSmsTopic'])
        );

        $this->assertFileWasAdded(
            self::ACTUAL_TOPIC_FILES['SendEmailTopic'],
            FileSystem::read(self::EXPECTED_TOPIC_FILES['SendEmailTopic'])
        );

        $this->assertTrue(!file_exists(__DIR__ . '/Fixture/fixture.php'));
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

    protected function assertFileWasAdded(string $expectedFilePath, string $expectedFileContents): void
    {
        // Ensure that the file exist
        $this->assertFileExists($expectedFilePath);
        // Ensure that generated file content is valid
        $actualFileContents = FileSystem::read($expectedFilePath);
        $this->assertSame($expectedFileContents, $actualFileContents);
    }
}
