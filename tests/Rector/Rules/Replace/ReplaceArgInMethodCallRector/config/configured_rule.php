<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\MethodCallArgReplace;
use Oro\UpgradeToolkit\Rector\Rules\Replace\ReplaceArgInMethodCallRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(
        ReplaceArgInMethodCallRector::class,
        [
            // method call replacement (positional + named args supported)
            new MethodCallArgReplace(
                class: 'App\\Mocks\\Foo',
                method: 'bar',
                argName: 'mode',
                oldValue: 'old',
                newValue: 'new',
            ),
            // static call replacement
            new MethodCallArgReplace(
                class: 'App\\Mocks\\Foo',
                method: 'staticBar',
                argName: 'mode',
                oldValue: 'old',
                newValue: 'new',
            ),
        ]
    );
};
