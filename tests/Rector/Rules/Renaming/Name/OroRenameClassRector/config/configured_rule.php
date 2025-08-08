<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\RenameClass;
use Oro\UpgradeToolkit\Rector\Rules\Renaming\Name\OroRenameClassRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(OroRenameClassRector::class, [
        // Rename OldBundle\OldClass to NewBundle\NewClass only in specified classes
        new RenameClass(
            'OldBundle\OldClass',
            'NewBundle\NewClass',
            ['Test\TestClass', 'Test\TestParentClass', 'Test\TestInterface']
        ),
        // Rename OldNamespace\Service to NewNamespace\Service
        new RenameClass(
            'OldNamespace\Service',
            'NewNamespace\Service',
            ['Test\ServiceUser']
        ),
        // Rename interface
        new RenameClass(
            'OldInterface\InterfaceA',
            'NewInterface\InterfaceA',
            ['Test\InterfaceImplementer']
        ),
    ]);
};
