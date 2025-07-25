<?php

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/skip-list.php');
    $rectorConfig->import(__DIR__ . '/oro-70/symfony.php');
};
