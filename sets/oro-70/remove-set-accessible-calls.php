<?php

use Oro\UpgradeToolkit\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    // As of PHP 8.1.0, calling `Reflection*::setAccessible()` has no effect.
    // https://www.php.net/manual/en/reflectionmethod.setaccessible.php
    // https://www.php.net/manual/en/reflectionproperty.setaccessible.php
    $rectorConfig->rule(RemoveReflectionSetAccessibleCallsRector::class);
};
