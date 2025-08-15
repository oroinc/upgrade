<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\SanitiseDocBlockRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(
        SanitiseDocBlockRector::class,
        [
            '"' => "'",  // Replace double quotes with single quotes
            '«' => "'",  // Replace left double angle quotation mark
            '»' => "'",  // Replace right double angle quotation mark
        ]
    );
};
