<?php

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Symfony73\Rector\Class_\GetFiltersToAsTwigFilterAttributeRector;
use Rector\Symfony\Symfony73\Rector\Class_\GetFunctionsToAsTwigFunctionAttributeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/skip-list.php');
    $rectorConfig->import(__DIR__ . '/oro-70/remove-sensio-framework-extra-bundle.php');

    $rectorConfig->sets([
        SymfonySetList::SYMFONY_70,
        SymfonySetList::SYMFONY_71,
        SymfonySetList::SYMFONY_72,
        SymfonySetList::SYMFONY_73,
        SymfonySetList::SYMFONY_74,
    ]);

    // Oro Twig extensions use the traditional AbstractExtension + twig.extension tag pattern.
    // The #[AsTwigFunction]/#[AsTwigFilter] attribute style is not used in Oro Platform.
    $rectorConfig->skip([
        GetFunctionsToAsTwigFunctionAttributeRector::class,
        GetFiltersToAsTwigFilterAttributeRector::class,
    ]);

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
        'Symfony\\Component\\DependencyInjection\\ContainerAwareInterface'
        => 'Oro\\Component\\DependencyInjection\\ContainerAwareInterface',
        'Symfony\\Component\\DependencyInjection\\ContainerAwareTrait'
        => 'Oro\\Component\\DependencyInjection\\ContainerAwareTrait',
    ]);
};
