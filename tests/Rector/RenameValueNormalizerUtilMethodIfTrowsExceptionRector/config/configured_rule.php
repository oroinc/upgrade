<?php

declare(strict_types=1);

use Oro\Rector\RenameValueNormalizerUtilMethodIfTrowsExceptionRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(RenameValueNormalizerUtilMethodIfTrowsExceptionRector::class);
};
