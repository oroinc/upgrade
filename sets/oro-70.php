<?php

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/skip-list.php');
    $rectorConfig->import(__DIR__ . '/oro-70/symfony.php');
    $rectorConfig->import(__DIR__ . '/oro-70/remove-sensio-framework-extra-bundle.php');
    $rectorConfig->import(__DIR__ . '/oro-70/remove-set-accessible-calls.php');

    // Use Oro\Component\Testing\Logger\TestLogger
    // instead of removed Psr\Log\Test\TestLogger
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Psr\\Log\\Test\\TestLogger' => 'Oro\\Component\\Testing\\Logger\\TestLogger',
    ]);
};
