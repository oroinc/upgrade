<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro70\Doctrine\AddTypeToSetParameterRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class AddTypeToSetParameterRectorTest extends AbstractRectorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure classes used in fixtures exist for ReflectionClass() calls inside the rector rule
        require_once __DIR__ . '/Mocks/Product.php';
        require_once __DIR__ . '/Mocks/Order.php';
        require_once __DIR__ . '/Mocks/Category.php';
        require_once __DIR__ . '/Mocks/ProductDto.php';
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
