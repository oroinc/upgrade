<?php

declare(strict_types=1);

use Oro\Rector\Rules\Oro51\TopicClassConstantUsageToTopicNameRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(TopicClassConstantUsageToTopicNameRector::class);
};
