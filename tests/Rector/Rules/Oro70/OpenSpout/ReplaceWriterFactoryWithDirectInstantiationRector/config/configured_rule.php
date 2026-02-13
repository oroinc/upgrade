<?php

use Oro\UpgradeToolkit\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ReplaceWriterFactoryWithDirectInstantiationRector::class);
};
