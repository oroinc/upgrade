<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\PropertyFetchWithConstructArgReplace;
use Oro\UpgradeToolkit\Rector\Rules\Replace\ReplacePropertyFetchWithConstructArgRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(
        ReplacePropertyFetchWithConstructArgRector::class,
        [
            new PropertyFetchWithConstructArgReplace(
                class: 'App\\Mocks\\TestClassAutoDetect'
            ),
            new PropertyFetchWithConstructArgReplace(
                class: 'App\\Mocks\\TestClassExplicit',
                properties: ['name', 'value']
            ),
        ]
    );
};
