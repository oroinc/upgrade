<?php

use Oro\UpgradeToolkit\Rector\Rules\Oro60\AddUserTypeCheckWhileAuthRector;
use Rector\Arguments\Rector\MethodCall\RemoveMethodCallParamRector;
use Rector\Arguments\ValueObject\RemoveMethodCallParam;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
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

    // EmailBundle
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EmailBundle\Manager\EmailTemplateManager' => 'Oro\Bundle\EmailBundle\Sender\EmailTemplateSender',
        'Oro\Bundle\EmailBundle\Tools\EmailTemplateSerializer' => 'Oro\Bundle\EmailBundle\Sender\EmailTemplateSender',
        'Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider' => 'Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider',
        'Oro\Bundle\EmailBundle\Provider\EmailTemplateContentProvider' => 'Oro\Bundle\EmailBundle\Provider\RenderedEmailTemplateProvider',
    ]);

    // Removed \Oro\Bundle\NotificationBundle\Manager\EmailNotificationSender,
    // use instead \Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager.
    //
    // Removed unused \Oro\Bundle\NotificationBundle\Model\EmailTemplate,
    // use instead \Oro\Bundle\EmailBundle\Model\EmailTemplate.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\NotificationBundle\Manager\EmailNotificationSender' => 'Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager',
        'Oro\Bundle\NotificationBundle\Model\EmailTemplate' => 'Oro\Bundle\EmailBundle\Model\EmailTemplate',
    ]);
};
