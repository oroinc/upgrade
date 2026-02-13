<?php

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/skip-list.php');
    $rectorConfig->import(__DIR__ . '/oro-70/remove-sensio-framework-extra-bundle.php');
    $rectorConfig->import(__DIR__ . '/oro-70/symfony.php');
    $rectorConfig->import(__DIR__ . '/oro-70/doctrine.php');
    $rectorConfig->import(__DIR__ . '/oro-70/openspout.php');
    $rectorConfig->import(__DIR__ . '/oro-70/symfony-code-quality.php');
    $rectorConfig->import(__DIR__ . '/oro-70/remove-set-accessible-calls.php');

    // Use Oro\Component\Testing\Logger\TestLogger
    // instead of removed Psr\Log\Test\TestLogger
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Psr\\Log\\Test\\TestLogger' => 'Oro\\Component\\Testing\\Logger\\TestLogger',
    ]);

    // Use Doctrine\DBAL\SQLParserUtils
    // instead of removed Oro\Component\DoctrineUtils\DBAL\SqlParserUtils
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Doctrine\\DBAL\\SQLParserUtils' => 'Oro\\Component\\DoctrineUtils\\DBAL\\SqlParserUtils',
    ]);

    // Use Oro\Component\DependencyInjection\ContainerAwareInterface
    // instead of removed Symfony\Component\DependencyInjection\ContainerAwareInterface
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\\Component\\DependencyInjection\\ContainerAwareInterface'
        => 'Symfony\\Component\\DependencyInjection\\ContainerAwareInterface',
        'Oro\\Component\\DependencyInjection\\ContainerAwareTrait'
        => 'Symfony\\Component\\DependencyInjection\\ContainerAwareTrait',
    ]);
};
