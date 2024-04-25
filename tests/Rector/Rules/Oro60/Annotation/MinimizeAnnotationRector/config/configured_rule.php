<?php

declare(strict_types=1);

use Oro\Rector\Rules\Oro60\Annotation\MinimizeAnnotationRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(
        MinimizeAnnotationRector::class,
        [
            'Config',
            'ConfigField',
        ]
    );
};
