<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\Doctrine\ReplaceUseResultCacheRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class ReplaceUseResultCacheRectorTest extends AbstractRectorTestCase
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
