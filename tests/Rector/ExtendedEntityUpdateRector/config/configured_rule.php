<?php

declare(strict_types=1);

use Oro\Rector\ExtendedEntityUpdateRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ExtendedEntityUpdateRector::class);
};
