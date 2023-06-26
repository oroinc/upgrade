<?php

declare(strict_types=1);

use Oro\Rector\TopicClassConstantUsageToTopicNameRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(TopicClassConstantUsageToTopicNameRector::class);
};
