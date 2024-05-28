<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector\Mocks\Topics\Topics::SEND_AUTO_RESPONSE'
        => 'Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponsesTopic::getName',

        'Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector\Mocks\Topics\Topics::SEND_AUTO_RESPONSES'
        => 'Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponsesTopic::getName',

        'Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector\Mocks\Topics\Topics::SEND_EMAIL_TEMPLATE'
        => 'Oro\Bundle\EmailBundle\Async\Topic\SendEmailTemplateTopic::getName',

        'Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector\Mocks\Topics\Topics::ADD_ASSOCIATION_TO_EMAIL'
        => 'Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationTopic::getName',
    ]);
};
