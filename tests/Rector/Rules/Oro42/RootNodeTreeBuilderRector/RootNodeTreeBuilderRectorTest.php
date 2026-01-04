<?php

namespace Oro\Tests\Rector\Rules\Oro42\RootNodeTreeBuilderRector;

use Rector\Exception\ShouldNotHappenException;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class RootNodeTreeBuilderRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData
     * @throws ShouldNotHappenException
     */
    public function test(string $filePath): void
    {
        $this->doTestFile($filePath);
    }

    public static function provideData(): \Iterator
    {
        return self::yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    #[\Override]
    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
