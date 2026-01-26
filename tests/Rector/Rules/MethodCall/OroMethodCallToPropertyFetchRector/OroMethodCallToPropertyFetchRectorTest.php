<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\MethodCall\OroMethodCallToPropertyFetchRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class OroMethodCallToPropertyFetchRectorTest extends AbstractRectorTestCase
{
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
