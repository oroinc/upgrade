<?php

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\AddGetDependenciesToEnumFixturesRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumClassInRepositoryFindByRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumClassInRepositoryFindRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumFindAllWithEnumOptionFindByRector;
use Oro\UpgradeToolkit\Rector\Signature\SignatureConfigurator;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\Rector\StaticCall\RenameStaticMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Renaming\ValueObject\RenameStaticMethod;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/skip-list.php');

    // Enums
    // Replaces enum find() with EnumOption::class + buildEnumOptionId()
    $rectorConfig->rule(ReplaceDynamicEnumClassInRepositoryFindRector::class);
    // Replaces enum ->findAll() with ->findBy(['enumCode' => ...])
    $rectorConfig->rule(ReplaceDynamicEnumFindAllWithEnumOptionFindByRector::class);
    // Replaces enum ->findOneBy(['name' => ...]) or ->findBy(['name' => ...])
    // with ->findOneBy(['id' => ...]) / ->findBy(['id' => ...])
    $rectorConfig->rule(ReplaceDynamicEnumClassInRepositoryFindByRector::class);

    // Class Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository was removed.
    // Use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository instead.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository'
        => 'Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository'
    ]);
    // Replace ->createEnumValue() call to ->createEnumOption()
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository',
            'createEnumValue',
            'createEnumOption'
        ),
    ]);

    // Adds missing LoadLanguageData dependency to the fixture classes
    $rectorConfig->rule(AddGetDependenciesToEnumFixturesRector::class);

    // Updated enums are inherited
    // not from the Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue
    // but from Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue'
        => 'Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface'
    ]);
    // Replace ->getId() call to ->getInternalId()
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface',
            'getId',
            'getInternalId'
        ),
    ]);

    // Class Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider was removed.
    // Use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider instead.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider'
        => 'Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider'
    ]);
    // Use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider::getEnumValueByCode() method
    // instead of Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider::getEnumValueByCode()
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
            new MethodCallRename(
                'Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider',
                'getEnumValueByCode',
                'getEnumOptionByCode'
            ),
    ]);

    $rectorConfig->ruleWithConfiguration(RenameStaticMethodRector::class, [
        new RenameStaticMethod(
            ExtendHelper::class,
            'buildEnumValueClassName',
            ExtendHelper::class,
            'buildEnumOptionId',
        ),
    ]);

    $rectorConfig->import(__DIR__ . '/oro-61/migrations.php');

    // Apply property/method signatures rules
    SignatureConfigurator::configure($rectorConfig);
};
