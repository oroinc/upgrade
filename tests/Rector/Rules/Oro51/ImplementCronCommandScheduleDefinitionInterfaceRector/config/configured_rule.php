<?php

declare(strict_types=1);

use Oro\Rector\Rules\Oro51\ImplementCronCommandScheduleDefinitionInterfaceRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ImplementCronCommandScheduleDefinitionInterfaceRector::class);
};
