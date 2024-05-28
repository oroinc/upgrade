<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Rules\Oro51\GenerateTopicClassesRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(GenerateTopicClassesRector::class);
};
