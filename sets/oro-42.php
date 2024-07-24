<?php

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/skip-list.php');

    $rectorConfig->sets([
        SymfonySetList::SYMFONY_40,
        SymfonySetList::SYMFONY_41,
    ]);
    // Customized Symfony42, Symfony43 sets
    $rectorConfig->import(__DIR__ . '/oro-42/symfony42.php');
    $rectorConfig->import(__DIR__ . '/oro-42/symfony43.php');
    $rectorConfig->sets([
        SymfonySetList::SYMFONY_44,
    ]);

    $rectorConfig->import(__DIR__ . '/oro-42/platform.php');
    $rectorConfig->import(__DIR__ . '/oro-42/commerce.php');
};
