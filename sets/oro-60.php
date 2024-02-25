<?php

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\FOSRestSetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/oro-60/skip-list.php');
    $rectorConfig->import(__DIR__ . '/oro-60/minimize-annotations.php');
    $rectorConfig->import(__DIR__ . '/oro-60/custom-attributes.php');
    $rectorConfig->import(__DIR__ . '/oro-60/constraint-annotations-to-attributes.php');

    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::GEDMO_ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SensiolabsSetList::FRAMEWORK_EXTRA_61,
        FOSRestSetList::ANNOTATIONS_TO_ATTRIBUTES
    ]);
};
