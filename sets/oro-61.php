<?php

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\Rector\PropertyFetch\RenamePropertyRector;
use Rector\Renaming\Rector\StaticCall\RenameStaticMethodRector;
use Rector\Renaming\ValueObject\RenameProperty;
use Rector\Renaming\ValueObject\RenameStaticMethod;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/skip-list.php');

    // Enums
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue'
        => 'Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface'
    ]);

    $rectorConfig->ruleWithConfiguration(RenameStaticMethodRector::class, [
        new RenameStaticMethod(
            ExtendHelper::class,
            'buildEnumValueClassName',
            ExtendHelper::class,
            'buildEnumOptionId',
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface'
        => 'Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareInterface'
    ]);

    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait'
        => 'Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait'
    ]);

    $rectorConfig->ruleWithConfiguration(RenamePropertyRector::class, [
        new RenameProperty(
            'Oro\Bundle\MigrationBundle\Migration\Migration',
            'extendExtension',
            'outdatedExtendExtension'
        )
    ]);
};
