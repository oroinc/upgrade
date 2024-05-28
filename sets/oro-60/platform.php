<?php

use Oro\UpgradeToolkit\Rector\Rules\Oro60\AddUserTypeCheckWhileAuthRector;
use Rector\Arguments\Rector\MethodCall\RemoveMethodCallParamRector;
use Rector\Arguments\ValueObject\RemoveMethodCallParam;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;

return static function (RectorConfig $rectorConfig): void {
    // SecurityBundle
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension',
            'addSecurityListenerFactory',
            'addAuthenticatorFactory'
        )
    ]);
    $rectorConfig->rule(AddUserTypeCheckWhileAuthRector::class);
    $rectorConfig->ruleWithConfiguration(RemoveMethodCallParamRector::class, [
        new RemoveMethodCallParam(
            'Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesser',
            'guess',
            1
        )
    ]);

    // ConfigBundle
    $rectorConfig->ruleWithConfiguration(RemoveMethodCallParamRector::class, [
        new RemoveMethodCallParam(
            'Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition',
            'getVariables',
            1
        ),
        new RemoveMethodCallParam(
            'Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition',
            'getVariables',
            0
        ),
    ]);
};
