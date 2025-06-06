<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Oro61\Enum\AddGetDependenciesToEnumFixturesRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class AddGetDependenciesToEnumFixturesRectorTest extends AbstractRectorTestCase
{
    public function testMethodAdding(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/add_method_fixture.php.inc');
    }

    public function testMethodEditing(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/edit_method_fixture.php.inc');
    }

    public function testMethodIsNotChanged(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/nothing_to_update_fixture.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
