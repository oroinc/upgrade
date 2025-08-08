<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\OroRenameProperty;
use Oro\UpgradeToolkit\Rector\Rules\Renaming\PropertyFetch\OroRenamePropertyRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(OroRenamePropertyRector::class, [
        // Rename oldProperty to newProperty in SomeClass only in specified classes
        new OroRenameProperty(
            'Test\SomeClass',
            'oldProperty',
            'newProperty',
            ['Test\TargetClass', 'Test\ParentClass', 'Test\InterfaceClass']
        ),
        // Rename anotherOldProperty to anotherNewProperty in AnotherClass
        new OroRenameProperty(
            'Test\AnotherClass',
            'anotherOldProperty',
            'anotherNewProperty',
            ['Test\TargetClass']
        ),
        // Rename property in interface implementer
        new OroRenameProperty(
            'Test\ServiceInterface',
            'serviceProperty',
            'newServiceProperty',
            ['Test\ServiceImplementer']
        ),
    ]);
};
