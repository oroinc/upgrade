<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumClassInRepositoryFindByRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class ReplaceDynamicEnumClassInRepositoryFindByRectorTest extends AbstractRectorTestCase
{
    public function test(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/fixture.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
