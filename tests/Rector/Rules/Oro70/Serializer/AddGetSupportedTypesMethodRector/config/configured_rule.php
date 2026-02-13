<?php

use Oro\UpgradeToolkit\Rector\Rules\Oro70\Serializer\AddGetSupportedTypesMethodRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(AddGetSupportedTypesMethodRector::class);
};
