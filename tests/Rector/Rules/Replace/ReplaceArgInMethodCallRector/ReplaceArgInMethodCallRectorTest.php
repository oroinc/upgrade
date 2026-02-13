<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Replace\ReplaceArgInMethodCallRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ReplaceArgInMethodCallRectorTest extends AbstractRectorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure classes used in fixtures exist for PHPStan reflection in ArgumentReplaceHelper
        require_once __DIR__ . '/Mocks/Foo.php';
        require_once __DIR__ . '/Mocks/FooChild.php';
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
