<?php

use Oro\UpgradeToolkit\Rector\Rules\Oro51\ImplementCronCommandScheduleDefinitionInterfaceRector;
use Rector\Config\RectorConfig;
use Rector\Transform\Rector\Class_\MergeInterfacesRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ImplementCronCommandScheduleDefinitionInterfaceRector::class);
    $rectorConfig->ruleWithConfiguration(MergeInterfacesRector::class, [
        'Oro\Bundle\CronBundle\Command\CronCommandInterface' => '\Oro\Bundle\CronBundle\Command\CronCommandActivationInterface',
    ]);
};
