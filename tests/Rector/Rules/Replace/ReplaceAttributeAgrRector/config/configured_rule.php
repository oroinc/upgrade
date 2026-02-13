<?php

use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\AttributeArgReplace;
use Oro\UpgradeToolkit\Rector\Rules\Replace\ReplaceAttributeAgrRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(
        ReplaceAttributeAgrRector::class,
        [
            new AttributeArgReplace(
                tag: 'Route',
                class: 'App\\Mocks\\Route',
                argName: 'name',
                oldValue: 'old',
                newValue: 'new',
            ),
            new AttributeArgReplace(
                tag: 'Route',
                class: 'App\\Mocks\\Route',
                argName: 'name',
                oldValue: 'old_positional',
                newValue: 'new_positional',
            ),
            new AttributeArgReplace(
                tag: 'Route',
                class: 'App\\Mocks\\Route',
                argName: 'methods',
                oldValue: ['GET'],
                newValue: ['POST'],
            ),
        ]
    );
};
