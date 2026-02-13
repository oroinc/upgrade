<?php

declare(strict_types=1);

namespace Rector\Rules\Oro70\Form\AddFormWidgetAndHtml5OptionsRector;

use Iterator;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class AddFormWidgetAndHtml5OptionsRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData
     */
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
