<?php

use Rector\Arguments\Rector\MethodCall\RemoveMethodCallParamRector;
use Rector\Arguments\ValueObject\RemoveMethodCallParam;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    // CustomerBundle
    $rectorConfig->ruleWithConfiguration(RemoveMethodCallParamRector::class, [
        new RemoveMethodCallParam(
            'Oro\Bundle\CustomerBundle\Security\Guesser\OrganizationGuesser',
            'guess',
            1
        )
    ]);

    // ActionBundle
    $rectorConfig->ruleWithConfiguration(RemoveMethodCallParamRector::class, [
        new RemoveMethodCallParam(
            'Oro\Bundle\ActionBundle\Model\Operation',
            'isAvailable',
            1
        )
    ]);
};
