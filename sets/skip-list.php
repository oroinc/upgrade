<?php

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    // Skip vendors, node_modules, etc. in the processed directory
    $skipList = [
        '*/vendor/*',
        '*/node_modules/*',
        '*/var/cache/*',
    ];

    if (
        'cli' === php_sapi_name()
        && ini_get('register_argc_argv')
        && array_key_exists('argv', $_SERVER)
    ) {
        $args = $_SERVER['argv'];
        if ($i = array_search('process', $args)) {
            $args = array_slice($args, $i + 1);
            foreach ($args as $argument) {
                if (str_starts_with($argument, '--')) {
                    break;
                }

                if (is_dir(realpath("./$argument"))) {
                    $processedDir = realpath("./$argument");

                    $skipList = [
                        "$processedDir*/vendor/*",
                        "$processedDir*/node_modules/*",
                        "$processedDir*/var/*",
                    ];
                }
            }
        }
    }

    $rectorConfig->skip($skipList);
};
