<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(RemoveReflectionSetAccessibleCallsRector::class);
};
