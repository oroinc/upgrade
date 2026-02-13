<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Replace\ReplaceAttributeAgrRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ReplaceAttributeAgrRectorTest extends AbstractRectorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/Mocks/Route.php';
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
