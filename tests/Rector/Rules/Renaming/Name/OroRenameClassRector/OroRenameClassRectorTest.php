<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Renaming\Name\OroRenameClassRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class OroRenameClassRectorTest extends AbstractRectorTestCase
{
    public function testRenameClassInApplyToList(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_class_in_apply_to_list.php.inc');
    }

    public function testRenameClassWithNamespace(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_class_with_namespace.php.inc');
    }

    public function testRenameClassConstantFetch(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_class_constant_fetch.php.inc');
    }

    public function testNoChangeIfClassNotInApplyToList(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/no_change_class_not_in_apply_to.php.inc');
    }

    public function testRenameInheritedClass(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_inherited_class.php.inc');
    }

    public function testRenameInterfaceImplementation(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_interface_implementation.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
