<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Rules\Oro42\MakeDispatchFirstArgumentEventRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(MakeDispatchFirstArgumentEventRector::class);
};
