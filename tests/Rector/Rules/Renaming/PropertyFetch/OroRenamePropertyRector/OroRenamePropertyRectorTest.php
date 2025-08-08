<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\Renaming\PropertyFetch\OroRenamePropertyRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

class OroRenamePropertyRectorTest extends AbstractRectorTestCase
{
    public function testRenamePropertyInTargetClass(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_property_in_target_class.php.inc');
    }

    public function testRenamePropertyFetchInTargetClass(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_property_fetch_in_target_class.php.inc');
    }

    public function testRenamePropertyInParentClass(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_property_in_parent_class.php.inc');
    }

    public function testRenamePropertyInInterfaceImplementer(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_property_in_interface_implementer.php.inc');
    }

    public function testNoChangeIfClassNotInApplyToList(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/no_change_class_not_in_apply_to.php.inc');
    }

    public function testNoChangeIfPropertyNotFound(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/no_change_property_not_found.php.inc');
    }

    public function testRenamePropertyFetchEvenWithConflictingProperty(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/no_change_conflicting_property_exists.php.inc');
    }

    public function testRenameMultipleProperties(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_multiple_properties.php.inc');
    }

    public function testRenamePropertyWithDifferentObjectTypes(): void
    {
        $this->doTestFile(__DIR__ . '/Fixture/rename_property_different_object_types.php.inc');
    }

    public function provideConfigFilePath(): string
    {
        return __DIR__ . '/config/configured_rule.php';
    }
}
