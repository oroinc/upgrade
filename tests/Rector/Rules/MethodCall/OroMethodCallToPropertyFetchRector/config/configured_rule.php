<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\MethodCall\ValueObject\MethodCallToPropertyFetch;
use Oro\UpgradeToolkit\Rector\Rules\MethodCall\OroMethodCallToPropertyFetchRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(OroMethodCallToPropertyFetchRector::class);

    $rectorConfig->ruleWithConfiguration(OroMethodCallToPropertyFetchRector::class, [
        new MethodCallToPropertyFetch(
            'Oro\UpgradeToolkit\Tests\Template',
            'setTemplate',
            'template'
        ),
        new MethodCallToPropertyFetch(
            'Oro\UpgradeToolkit\Tests\Template',
            'getTemplate',
            'template'
        ),
        new MethodCallToPropertyFetch(
            'Oro\UpgradeToolkit\Tests\Config',
            'setValue',
            'value'
        ),
        new MethodCallToPropertyFetch(
            'Oro\UpgradeToolkit\Tests\Config',
            'getValue',
            'value'
        ),
        new MethodCallToPropertyFetch(
            'Oro\UpgradeToolkit\Tests\Form',
            'setName',
            'name'
        ),
        new MethodCallToPropertyFetch(
            'Oro\UpgradeToolkit\Tests\Form',
            'getName',
            'name'
        ),
    ]);
};
