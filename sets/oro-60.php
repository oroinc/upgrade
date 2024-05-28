<?php

use Oro\UpgradeToolkit\Rector\Signature\SignatureConfigurator;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Symfony\Set\FOSRestSetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/skip-list.php');
    $rectorConfig->import(__DIR__ . '/oro-60/minimize-annotations.php');
    $rectorConfig->import(__DIR__ . '/oro-60/custom-attributes.php');
    $rectorConfig->import(__DIR__ . '/oro-60/constraint-annotations-to-attributes.php');

    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::GEDMO_ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SensiolabsSetList::ANNOTATIONS_TO_ATTRIBUTES,
        FOSRestSetList::ANNOTATIONS_TO_ATTRIBUTES
    ]);

    // Replace Session usage to RequestStack
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Symfony\Component\HttpFoundation\Session\Session' => 'Symfony\Component\HttpFoundation\RequestStack'
    ]);

    $rectorConfig->sets([
        SymfonySetList::SYMFONY_51,
        SymfonySetList::SYMFONY_52,
        SymfonySetList::SYMFONY_52_VALIDATOR_ATTRIBUTES,
        SymfonySetList::SYMFONY_53,
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_60,
        SymfonySetList::SYMFONY_61,
    ]);
    // Customized Symfony62 set
    $rectorConfig->import(__DIR__ . '/oro-60/symfony62.php');
    $rectorConfig->sets([
        //SymfonySetList::SYMFONY_62,
        SymfonySetList::SYMFONY_63,
        SymfonySetList::SYMFONY_64,
    ]);

    // Customized code quality set
    $rectorConfig->import(__DIR__ . '/oro-60/symfony-code-quality.php');

    $rectorConfig->import(__DIR__ . '/oro-60/commerce.php');
    $rectorConfig->import(__DIR__ . '/oro-60/customer-portal.php');
    $rectorConfig->import(__DIR__ . '/oro-60/platform.php');

    // Apply property/method signatures rules
    SignatureConfigurator::configure($rectorConfig);
};
