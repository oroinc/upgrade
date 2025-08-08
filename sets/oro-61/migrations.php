<?php

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\OroRenameProperty;
use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\RenameClass;
use Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceExtendExtensionAwareTraitRector;
use Oro\UpgradeToolkit\Rector\Rules\Renaming\Name\OroRenameClassRector;
use Oro\UpgradeToolkit\Rector\Rules\Renaming\PropertyFetch\OroRenamePropertyRector;
use Rector\Config\RectorConfig;

/**
 * This ruleset should be updated or excluded for Oro70 and higher
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/../skip-list.php');

    $rectorConfig->ruleWithConfiguration(
        OroRenameClassRector::class,
        [
        new RenameClass(
            oldClass: 'Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface',
            newClass: 'Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareInterface',
            applyTo: ['Oro\Bundle\MigrationBundle\Migration\Migration'],
        )
    ]
    );

    $rectorConfig->ruleWithConfiguration(OroRenamePropertyRector::class, [
        new OroRenameProperty(
            type: 'Oro\Bundle\MigrationBundle\Migration\Migration',
            oldProperty: 'extendExtension',
            newProperty: 'outdatedExtendExtension',
            applyTo: ['Oro\Bundle\MigrationBundle\Migration\Migration']
        )
    ]);

    $rectorConfig->rule(ReplaceExtendExtensionAwareTraitRector::class);
};
