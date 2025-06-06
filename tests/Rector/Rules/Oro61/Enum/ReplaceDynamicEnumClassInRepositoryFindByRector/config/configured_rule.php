<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum\ReplaceDynamicEnumClassInRepositoryFindByRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ReplaceDynamicEnumClassInRepositoryFindByRector::class);
};
