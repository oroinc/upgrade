<?php

declare(strict_types=1);

use Oro\Rector\Rules\Oro60\AddUserTypeCheckWhileAuthRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(AddUserTypeCheckWhileAuthRector::class);
};
