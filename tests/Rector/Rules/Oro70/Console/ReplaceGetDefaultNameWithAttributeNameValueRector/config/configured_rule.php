<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Rules\Oro70\Console\ReplaceGetDefaultNameWithAttributeNameValueRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ReplaceGetDefaultNameWithAttributeNameValueRector::class);
};
