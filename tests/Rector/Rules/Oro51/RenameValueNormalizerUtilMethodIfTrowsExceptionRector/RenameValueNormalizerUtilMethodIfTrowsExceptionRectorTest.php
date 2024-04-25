<?php

declare(strict_types=1);

namespace Oro\Tests\Rector\Rules\Oro51\RenameValueNormalizerUtilMethodIfTrowsExceptionRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class RenameValueNormalizerUtilMethodIfTrowsExceptionRectorTest extends AbstractRectorTestCase
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
