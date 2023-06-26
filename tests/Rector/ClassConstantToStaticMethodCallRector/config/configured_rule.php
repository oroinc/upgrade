<?php

declare(strict_types=1);

use Oro\Rector\ClassConstantToStaticMethodCallRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\Bundle\RedirectBundle\Async\Topics::GENERATE_DIRECT_URL_FOR_ENTITIES' => 'Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic::getName',
        'Oro\Bundle\RedirectBundle\Async\Topics::JOB_GENERATE_DIRECT_URL_FOR_ENTITIES' => 'Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesJobAwareTopic::getName',
        'Oro\Bundle\RedirectBundle\Async\Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE' => 'Oro\Bundle\RedirectBundle\Async\Topic\RegenerateDirectUrlForEntityTypeTopic::getName',
        'Oro\Bundle\RedirectBundle\Async\Topics::REMOVE_DIRECT_URL_FOR_ENTITY_TYPE' => 'Oro\Bundle\RedirectBundle\Async\Topic\RemoveDirectUrlForEntityTypeTopic::getName',
    ]);
};
