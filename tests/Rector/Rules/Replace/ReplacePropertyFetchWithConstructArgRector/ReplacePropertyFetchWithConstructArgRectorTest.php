<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Replace\ReplacePropertyFetchWithConstructArgRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class ReplacePropertyFetchWithConstructArgRectorTest extends AbstractRectorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure classes used in fixtures exist for ReflectionClass() calls inside the rector rule
        require_once __DIR__ . '/Mocks/TestClassAutoDetect.php';
        require_once __DIR__ . '/Mocks/TestClassExplicit.php';
    }

    /**
     * @dataProvider provideData
     */
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
