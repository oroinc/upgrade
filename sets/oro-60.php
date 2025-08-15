<?php

use Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Symfony\Set\FOSRestSetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/skip-list.php');
    $rectorConfig->import(__DIR__ . '/oro-60/preprocess-annotations.php');
    $rectorConfig->import(__DIR__ . '/oro-60/oro-attributes.php');
    $rectorConfig->import(__DIR__ . '/oro-60/oro-constraint-annotations-to-attributes.php');

    /** @see \Symfony\Component\Routing\Annotation\Route */
    $rectorConfig->ruleWithConfiguration(RenameNamespaceRector::class, [
        'Symfony\Component\Routing\Annotation' => 'Symfony\Component\Routing\Attribute',
    ]);

    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::GEDMO_ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SensiolabsSetList::ANNOTATIONS_TO_ATTRIBUTES,
        FOSRestSetList::ANNOTATIONS_TO_ATTRIBUTES
    ]);
    $rectorConfig->import(__DIR__ . '/oro-60/doctrine.php');

    // Replace Session usage to RequestStack
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Symfony\Component\HttpFoundation\Session\Session' => 'Symfony\Component\HttpFoundation\RequestStack'
    ]);

    $rectorConfig->sets([
        SymfonySetList::SYMFONY_60,
        SymfonySetList::SYMFONY_61,
    ]);
    // Customized Symfony62 set
    $rectorConfig->import(__DIR__ . '/oro-60/symfony62.php');
    $rectorConfig->sets([
        SymfonySetList::SYMFONY_63,
        SymfonySetList::SYMFONY_64,
    ]);

    // Customized code quality set
    $rectorConfig->import(__DIR__ . '/oro-60/symfony-code-quality.php');

    $rectorConfig->import(__DIR__ . '/oro-60/commerce.php');
    $rectorConfig->import(__DIR__ . '/oro-60/customer-portal.php');
    $rectorConfig->import(__DIR__ . '/oro-60/platform.php');

    $rectorConfig->import(__DIR__ . '/oro-60/add_override_attribute.php');
};
