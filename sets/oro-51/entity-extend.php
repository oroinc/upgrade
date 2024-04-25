<?php

use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Rector\Rules\Oro51\ExtendedEntityUpdateRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Transform\Rector\FuncCall\FuncCallToStaticCallRector;
use Rector\Transform\Rector\New_\NewToStaticCallRector;
use Rector\Transform\ValueObject\FuncCallToStaticCall;
use Rector\Transform\ValueObject\NewToStaticCall;

return static function (RectorConfig $rectorConfig): void {
    // -- EXTENDED ENTITIES
    $rectorConfig->rule(ExtendedEntityUpdateRector::class);

    // -- PROPERTY ACCESSOR
    $rectorConfig->ruleWithConfiguration(NewToStaticCallRector::class, [
        new NewToStaticCall(
            'Oro\Component\PropertyAccess\PropertyAccessor',
            PropertyAccess::class,
            'createPropertyAccessor'
        ),
    ]);
    $rectorConfig->ruleWithConfiguration(FuncCallToStaticCallRector::class, [
        new FuncCallToStaticCall('property_exists', EntityPropertyInfo::class, 'propertyExists'),
        new FuncCallToStaticCall('method_exists', EntityPropertyInfo::class, 'methodExists'),
    ]);
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'ReflectionClass' => 'Oro\Bundle\EntityExtendBundle\EntityReflectionClass',
        'ReflectionProperty' => 'Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\ReflectionVirtualProperty',
        'ReflectionMethod' => 'Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\VirtualReflectionMethod',
    ]);
};
