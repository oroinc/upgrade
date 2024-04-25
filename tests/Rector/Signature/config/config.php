<?php

declare(strict_types=1);

use Oro\Rector\Signature\SignatureConfigurator;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    SignatureConfigurator::configure($rectorConfig);
};
