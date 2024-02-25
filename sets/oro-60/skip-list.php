<?php

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    // Skip vendors, node_modules, etc
    $rectorConfig->skip([
        '*/vendor/*',
        '*/node_modules/*',
        '*/var/*',
    ]);
};
