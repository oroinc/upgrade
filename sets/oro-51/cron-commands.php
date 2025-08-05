<?php

use Oro\UpgradeToolkit\Rector\Rules\Oro51\ImplementCronCommandScheduleDefinitionInterfaceRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ImplementCronCommandScheduleDefinitionInterfaceRector::class);
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\CronBundle\Command\CronCommandInterface' => 'Oro\Bundle\CronBundle\Command\CronCommandActivationInterface',
    ]);
};
