<?php

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\String_\RenameStringRector;

return static function (RectorConfig $rectorConfig): void {
    // v5.0.0

    // Website search field category_path_CATEGORY_PATH
    // has been renamed to category_paths.CATEGORY_PATH
    $rectorConfig->ruleWithConfiguration(RenameStringRector::class, [
        'category_path_CATEGORY_PATH' => 'category_paths.CATEGORY_PATH',
    ]);

    // Website search field assigned_to_ASSIGN_TYPE_ASSIGN_ID
    // has been renamed to assigned_to.ASSIGN_TYPE_ASSIGN_ID.CATEGORY_PATH
    $rectorConfig->ruleWithConfiguration(RenameStringRector::class, [
        'assigned_to_ASSIGN_TYPE_ASSIGN_ID' => 'assigned_to.ASSIGN_TYPE_ASSIGN_ID.CATEGORY_PATH',
    ]);

    // Website search field manually_added_to_ASSIGN_TYPE_ASSIGN_ID
    // has been renamed to manually_added_to.ASSIGN_TYPE_ASSIGN_ID
    $rectorConfig->ruleWithConfiguration(RenameStringRector::class, [
        'manually_added_to_ASSIGN_TYPE_ASSIGN_ID' => 'manually_added_to.ASSIGN_TYPE_ASSIGN_ID',
    ]);

    // Website search field visibility_customer_CUSTOMER_ID
    // has been renamed to visibility_customer.CUSTOMER_ID
    $rectorConfig->ruleWithConfiguration(RenameStringRector::class, [
        'visibility_customer_CUSTOMER_ID' => 'visibility_customer.CUSTOMER_ID',
    ]);
};
