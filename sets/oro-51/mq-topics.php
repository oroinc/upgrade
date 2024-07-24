<?php

use Oro\UpgradeToolkit\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro51\GenerateTopicClassesRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro51\TopicClassConstantUsageToTopicNameRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(GenerateTopicClassesRector::class);
    $rectorConfig->rule(TopicClassConstantUsageToTopicNameRector::class);

    // Replace constant with the static method call
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\Bundle\RedirectBundle\Async\Topics::GENERATE_DIRECT_URL_FOR_ENTITIES' => 'Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic::getName',
        'Oro\Bundle\RedirectBundle\Async\Topics::JOB_GENERATE_DIRECT_URL_FOR_ENTITIES' => 'Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesJobAwareTopic::getName',
        'Oro\Bundle\RedirectBundle\Async\Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE' => 'Oro\Bundle\RedirectBundle\Async\Topic\RegenerateDirectUrlForEntityTypeTopic::getName',
        'Oro\Bundle\RedirectBundle\Async\Topics::REMOVE_DIRECT_URL_FOR_ENTITY_TYPE' => 'Oro\Bundle\RedirectBundle\Async\Topic\RemoveDirectUrlForEntityTypeTopic::getName',
        'Oro\Bundle\RedirectBundle\Async\Topics::SYNC_SLUG_REDIRECTS' => 'Oro\Bundle\RedirectBundle\Async\Topic\SyncSlugRedirectsTopic::getName',
        'Oro\Bundle\RedirectBundle\Async\Topics::CALCULATE_URL_CACHE_MASS' => 'Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheMassTopic::getName',
        'Oro\Bundle\RedirectBundle\Async\Topics::PROCESS_CALCULATE_URL_CACHE' => 'Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheTopic::getName',
        'Oro\Bundle\VisibilityBundle\Async\Topics::CHANGE_PRODUCT_CATEGORY' => 'Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeProductCategoryTopic::getName',
        'Oro\Bundle\VisibilityBundle\Async\Topics::RESOLVE_PRODUCT_VISIBILITY' => 'Oro\Bundle\VisibilityBundle\Async\Topic\ResolveProductVisibilityTopic::getName',
        'Oro\Bundle\VisibilityBundle\Async\Topics::CHANGE_CATEGORY_VISIBILITY' => 'Oro\Bundle\VisibilityBundle\Async\Topic\ResolveCategoryVisibilityTopic::getName',
        'Oro\Bundle\VisibilityBundle\Async\Topics::CHANGE_CUSTOMER' => 'Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeCustomerTopic::getName',
        'Oro\Bundle\VisibilityBundle\Async\Topics::CATEGORY_POSITION_CHANGE' => 'Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeCategoryPositionTopic::getName',
        'Oro\Bundle\VisibilityBundle\Async\Topics::CATEGORY_REMOVE' => 'Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnRemoveCategoryTopic::getName',
        'Oro\Bundle\WebCatalogBundle\Async\Topics::CALCULATE_WEB_CATALOG_CACHE' => 'Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic::getName',
        'Oro\Bundle\WebCatalogBundle\Async\Topics::CALCULATE_CONTENT_NODE_CACHE' => 'Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic::getName',
        'Oro\Bundle\WebCatalogBundle\Async\Topics::CALCULATE_CONTENT_NODE_TREE_BY_SCOPE' => 'Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeTreeCacheTopic::getName',
        'Oro\Bundle\WebCatalogBundle\Async\Topics::RESOLVE_NODE_SLUGS' => 'Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic::getName',
        'Oro\Bundle\ShoppingListBundle\Async\Topics::INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_PRODUCT' => 'Oro\Bundle\ShoppingListBundle\Async\Topic\InvalidateTotalsByInventoryStatusPerProductTopic::getName',
        'Oro\Bundle\ShoppingListBundle\Async\Topics::INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_WEBSITE' => 'Oro\Bundle\ShoppingListBundle\Async\Topic\InvalidateTotalsByInventoryStatusPerWebsiteTopic::getName',
        'Oro\Bundle\PricingBundle\Async\Topics::RESOLVE_PRICE_RULES' => 'Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic::getName',
        'Oro\Bundle\PricingBundle\Async\Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS' => 'Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic::getName',
        'Oro\Bundle\PricingBundle\Async\Topics::REBUILD_COMBINED_PRICE_LISTS' => 'Oro\Bundle\PricingBundle\Async\Topic\RebuildCombinedPriceListsTopic::getName',
        'Oro\Bundle\PricingBundle\Async\Topics::RESOLVE_COMBINED_PRICES' => 'Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic::getName',
        'Oro\Bundle\PricingBundle\Async\Topics::RESOLVE_COMBINED_CURRENCIES' => 'Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceListCurrenciesTopic::getName',
        'Oro\Bundle\ProductBundle\Async\Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT' => 'Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic::getName',
        'Oro\Bundle\ProductBundle\Async\Topics::REINDEX_PRODUCTS_BY_ATTRIBUTES' => 'oro_product.reindex_products_by_attributes',
        'Oro\Bundle\ProductBundle\Async\Topics::PRODUCT_IMAGE_RESIZE' => 'Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic::getName',
        'Oro\Bundle\CheckoutBundle\Async\Topics::RECALCULATE_CHECKOUT_SUBTOTALS' => 'Oro\Bundle\CheckoutBundle\Async\Topic\RecalculateCheckoutSubtotalsTopic::getName',
        'Oro\Bundle\SEOBundle\Async\Topics::GENERATE_SITEMAP' => 'Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapTopic::getName',
        'Oro\Bundle\SEOBundle\Async\Topics::GENERATE_SITEMAP_INDEX' => 'Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapIndexTopic::getName',
        'Oro\Bundle\SEOBundle\Async\Topics::GENERATE_SITEMAP_BY_WEBSITE_AND_TYPE' => 'Oro\Bundle\SEOBundle\Async\Topic\GenerateSitemapByWebsiteAndTypeTopic::getName',
        'Oro\Bridge\CustomerAccount\Async\Topics::REASSIGN_CUSTOMER_ACCOUNT' => 'Oro\Bridge\CustomerAccount\Async\Topic\ReassignCustomerAccountTopic::getName',
        'Oro\Bundle\UserProBundle\Async\Topics::EXPIRE_USER_PASSWORD' => 'Oro\Bundle\UserProBundle\Async\Topic\ExpireUserPasswordTopic::getName',
        'Oro\Bundle\UserProBundle\Async\Topics::EXPIRE_USER_PASSWORDS' => 'Oro\Bundle\UserProBundle\Async\Topic\ExpireUserPasswordsTopic::getName',
        'Oro\Bundle\AttachmentBundle\Async\Topics::ATTACHMENT_REMOVE_IMAGE' => 'Oro\Bundle\AttachmentBundle\Async\Topic\AttachmentRemoveImageTopic::getName',
        'Oro\Bundle\WorkflowBundle\Async\Topics::EXECUTE_PROCESS_JOB' => 'Oro\Bundle\WorkflowBundle\Async\Topic\ExecuteProcessJobTopic::getName',
        'Oro\Bundle\IntegrationBundle\Async\Topics::SYNC_INTEGRATION' => 'Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic::getName',
        'Oro\Bundle\IntegrationBundle\Async\Topics::REVERS_SYNC_INTEGRATION' => 'Oro\Bundle\IntegrationBundle\Async\Topic\ReverseSyncIntegrationTopic::getName',
        'Oro\Bundle\DataGridBundle\Async\Topics::PRE_EXPORT' => 'Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic::getName',
        'Oro\Bundle\DataGridBundle\Async\Topics::EXPORT' => 'Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic::getName',
        'Oro\Bundle\ApiBundle\Batch\Async\Topics::UPDATE_LIST' => 'Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListTopic::getName',
        'Oro\Bundle\ApiBundle\Batch\Async\Topics::UPDATE_LIST_CREATE_CHUNK_JOBS' => 'Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListCreateChunkJobsTopic::getName',
        'Oro\Bundle\ApiBundle\Batch\Async\Topics::UPDATE_LIST_START_CHUNK_JOBS' => 'Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListStartChunkJobsTopic::getName',
        'Oro\Bundle\ApiBundle\Batch\Async\Topics::UPDATE_LIST_PROCESS_CHUNK' => 'Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListProcessChunkTopic::getName',
        'Oro\Bundle\ApiBundle\Batch\Async\Topics::UPDATE_LIST_FINISH' => 'Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListFinishTopic::getName',
        'Oro\Bundle\ImportExportBundle\Async\Topics::PRE_IMPORT' => 'Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic::getName',
        'Oro\Bundle\ImportExportBundle\Async\Topics::IMPORT' => 'Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic::getName',
        'Oro\Bundle\ImportExportBundle\Async\Topics::PRE_EXPORT' => 'Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic::getName',
        'Oro\Bundle\ImportExportBundle\Async\Topics::EXPORT' => 'Oro\Bundle\ImportExportBundle\Async\Topic\ExportTopic::getName',
        'Oro\Bundle\ImportExportBundle\Async\Topics::POST_EXPORT' => 'Oro\Bundle\ImportExportBundle\Async\Topic\PostExportTopic::getName',
        'Oro\Bundle\ImportExportBundle\Async\Topics::SEND_IMPORT_NOTIFICATION' => 'Oro\Bundle\ImportExportBundle\Async\Topic\SendImportNotificationTopic::getName',
        'Oro\Bundle\ImportExportBundle\Async\Topics::SAVE_IMPORT_EXPORT_RESULT' => 'Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic::getName',
        'Oro\Bundle\ImportExportBundle\Async\Topics::PRE_HTTP_IMPORT' => 'Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic::getName',
        'Oro\Bundle\ImportExportBundle\Async\Topics::HTTP_IMPORT' => 'Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic::getName',
        'Oro\Bundle\DotmailerBundle\Async\Topics::EXPORT_CONTACTS_STATUS_UPDATE' => 'Oro\Bundle\DotmailerBundle\Async\Topic\ExportContactsStatusUpdateTopic::getName',
        'Oro\Bundle\AnalyticsBundle\Async\Topics::CALCULATE_ALL_CHANNELS_ANALYTICS' => 'Oro\Bundle\AnalyticsBundle\Async\Topic\CalculateAllChannelsAnalyticsTopic::getName',
        'Oro\Bundle\AnalyticsBundle\Async\Topics::CALCULATE_CHANNEL_ANALYTICS' => 'Oro\Bundle\AnalyticsBundle\Async\Topic\CalculateChannelAnalyticsTopic::getName',
        'Oro\Bundle\ChannelBundle\Async\Topics::CHANNEL_STATUS_CHANGED' => 'Oro\Bundle\ChannelBundle\Async\Topic\ChannelStatusChangedTopic::getName',
        'Oro\Bundle\ChannelBundle\Async\Topics::AGGREGATE_LIFETIME_AVERAGE' => 'Oro\Bundle\ChannelBundle\Async\Topic\AggregateLifetimeAverageTopic::getName',
        'Oro\Bundle\ContactBundle\Async\Topics::ACTUALIZE_CONTACT_EMAIL_ASSOCIATIONS' => 'Oro\Bundle\ContactBundle\Async\Topic\ActualizeContactEmailAssociationsTopic::getName',
        'Oro\Bundle\MultiWebsiteBundle\Async\Topics::BUILD_WEBSITE_CACHE' => 'Oro\Bundle\MultiWebsiteBundle\Async\Topic\VisibilityOnChangeWebsiteTopic::getName',
        'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topics::SAVED_SEARCH_TRIGGERS_HANDLE' => 'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topic\SavedSearchTriggersHandleTopic::getName',
        'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topics::SAVED_SEARCH_RESULT_SET_UPDATE' => 'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topic\SavedSearchResultSetUpdateTopic::getName',
        'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topics::SAVED_SEARCH_RESULT_SET_UPDATE_CHUNK' => 'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topic\SavedSearchResultSetUpdateChunkTopic::getName',
        'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topics::SAVED_SEARCH_PERCOLATE_PRODUCTS' => 'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topic\SavedSearchPercolateProductsTopic::getName',
        'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topics::SAVED_SEARCH_PERCOLATION_RESULTS_HANDLE' => 'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topic\SavedSearchPercolationResultsHandleTopic::getName',
        'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topics::SAVED_SEARCH_RESULT_SET_RECREATE' => 'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topic\SavedSearchResultSetRecreateTopic::getName',
        'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topics::SAVED_SEARCH_RESULT_SET_CREATE' => 'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topic\SavedSearchResultSetCreateTopic::getName',
        'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topics::SAVED_SEARCH_RESULT_SET_CREATE_ITEMS' => 'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topic\SavedSearchResultSetCreateItemsTopic::getName',
        'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topics::SAVED_SEARCH_ALERTS_HANDLE' => 'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topic\SavedSearchAlertsHandleTopic::getName',
        'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topics::SAVED_SEARCH_ALERTS_NOTIFICATIONS_SEND' => 'Oro\Bundle\WebsiteElasticSearchBundle\Async\Topic\SavedSearchAlertsNotificationsSendTopic::getName',
        'Oro\Bundle\WarehouseBundle\Async\Topics::ADD_INVENTORY_LEVELS' => 'Oro\Bundle\WarehouseBundle\Async\Topic\AddInventoryLevelsTopic::getName',
        'Oro\Bundle\TerritoryBundle\Async\Topics::UPDATE_ENTITY_TERRITORY' => 'Oro\Bundle\TerritoryBundle\Async\Topic\UpdateEntityTerritoriesTopic::getName',
        'Oro\Bundle\TerritoryBundle\Async\Topics::UPDATE_TERRITORY_DEFINITION' => 'Oro\Bundle\TerritoryBundle\Async\Topic\UpdateEntitiesByTerritoryTopic::getName',
        'Oro\Bundle\TerritoryBundle\Async\Topics::UPDATE_TOP_TERRITORY' => 'Oro\Bundle\TerritoryBundle\Async\Topic\UpdateEntityWithTopTerritoryTopic::getName',
        'Oro\Bundle\TrackingBundle\Async\Topics::AGGREGATE_VISITS' => 'Oro\Bundle\TrackingBundle\Async\Topic\TrackingAggregateVisitsTopic::getName',
        'Oro\Bundle\CampaignBundle\Async\Topics::SEND_EMAIL_CAMPAIGN' => 'Oro\Bundle\CampaignBundle\Async\Topic\SendEmailCampaignTopic::getName',
        'Oro\Bundle\CustomerBundle\Async\Topics::CALCULATE_OWNER_TREE_CACHE' => 'Oro\Bundle\CustomerBundle\Async\Topic\CustomerCalculateOwnerTreeCacheTopic::getName',
        'Oro\Bundle\CustomerBundle\Async\Topics::CALCULATE_BUSINESS_UNIT_OWNER_TREE_CACHE' => 'Oro\Bundle\CustomerBundle\Async\Topic\CustomerCalculateOwnerTreeCacheByBusinessUnitTopic::getName',
        'Oro\Bundle\FrontendImportExportBundle\Async\Topics::PRE_EXPORT' => 'Oro\Bundle\FrontendImportExportBundle\Async\Topic\PreExportTopic::getName',
        'Oro\Bundle\FrontendImportExportBundle\Async\Topics::EXPORT' => 'Oro\Bundle\FrontendImportExportBundle\Async\Topic\ExportTopic::getName',
        'Oro\Bundle\FrontendImportExportBundle\Async\Topics::POST_EXPORT' => 'Oro\Bundle\FrontendImportExportBundle\Async\Topic\PostExportTopic::getName',
        'Oro\Bundle\FrontendImportExportBundle\Async\Topics::SAVE_EXPORT_RESULT' => 'Oro\Bundle\FrontendImportExportBundle\Async\Topic\SaveExportResultTopic::getName',
        'Oro\Bundle\MicrosoftSyncBundle\Async\Topics::SYNC_CALENDARS_FOR_ALL_USERS' => 'Oro\Bundle\MicrosoftSyncBundle\Async\Topic\SyncCalendarsForAllUsersTopic::getName',
        'Oro\Bundle\MicrosoftSyncBundle\Async\Topics::SYNC_CALENDAR_FOR_USER' => 'Oro\Bundle\MicrosoftSyncBundle\Async\Topic\SyncCalendarForUserTopic::getName',
        'Oro\Bundle\MicrosoftSyncBundle\Async\Topics::RE_SYNC_CALENDARS_FOR_ALL_USERS' => 'Oro\Bundle\MicrosoftSyncBundle\Async\Topic\ReSyncCalendarsForAllUsersTopic::getName',
        'Oro\Bundle\MicrosoftSyncBundle\Async\Topics::RE_SYNC_CALENDAR_FOR_USER' => 'Oro\Bundle\MicrosoftSyncBundle\Async\Topic\ReSyncCalendarForUserTopic::getName',
        'Oro\Bundle\MicrosoftSyncBundle\Async\Topics::SYNC_CALENDAR_EVENT' => 'Oro\Bundle\MicrosoftSyncBundle\Async\Topic\SyncCalendarEventTopic::getName',
        'Oro\Bundle\MicrosoftSyncBundle\Async\Topics::SYNC_TASKS_FOR_ALL_USERS' => 'Oro\Bundle\MicrosoftSyncBundle\Async\Topic\SyncTasksForAllUsersTopic::getName',
        'Oro\Bundle\MicrosoftSyncBundle\Async\Topics::SYNC_TASKS_FOR_USER' => 'Oro\Bundle\MicrosoftSyncBundle\Async\Topic\SyncTaskForUserTopic::getName',
        'Oro\Bundle\MicrosoftSyncBundle\Async\Topics::SYNC_TASK' => 'Oro\Bundle\MicrosoftSyncBundle\Async\Topic\SyncTaskTopic::getName',
    ]);

    // Removed Oro\Bundle\CronBundle\Async\Topics,
    // use getName() of corresponding topic class from Oro\Bundle\CronBundle\Async\Topic namespace instead.
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\Bundle\CronBundle\Async\Topics::RUN_COMMAND' => 'Oro\Bundle\CronBundle\Async\Topic\RunCommandTopic::getName',
        'Oro\Bundle\CronBundle\Async\Topics::RUN_COMMAND_DELAYED' => 'Oro\Bundle\CronBundle\Async\Topic\RunCommandDelayedTopic::getName',
    ]);

    // Removed Oro\Bundle\DataAuditBundle\Async\Topics,
    // use getName() of corresponding topic class from Oro\Bundle\DataAuditBundle\Async\Topic namespace instead.
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\Bundle\DataAuditBundle\Async\Topics::ENTITIES_CHANGED' => 'Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesTopic::getName',
        'Oro\Bundle\DataAuditBundle\Async\Topics::ENTITIES_RELATIONS_CHANGED' => 'Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesRelationsTopic::getName',
        'Oro\Bundle\DataAuditBundle\Async\Topics::ENTITIES_INVERSED_RELATIONS_CHANGED' => 'Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseRelationsTopic::getName',
        'Oro\Bundle\DataAuditBundle\Async\Topics::ENTITIES_INVERSED_RELATIONS_CHANGED_COLLECTIONS' => 'Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsTopic::getName',
        'Oro\Bundle\DataAuditBundle\Async\Topics::ENTITIES_INVERSED_RELATIONS_CHANGED_COLLECTIONS_CHUNK' => 'Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesInverseCollectionsChunkTopic::getName',
    ]);

    // Removed Oro\Bundle\EmailBundle\Async\Topics,
    // use getName() of corresponding topic class from Oro\Bundle\EmailBundle\Async\Topic namespace instead.
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\Bundle\EmailBundle\Async\Topics::SEND_AUTO_RESPONSE' => 'Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponseTopic::getName',
        'Oro\Bundle\EmailBundle\Async\Topics::SEND_AUTO_RESPONSES' => 'Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponsesTopic::getName',
        'Oro\Bundle\EmailBundle\Async\Topics::ADD_ASSOCIATION_TO_EMAIL' => 'Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationTopic::getName',
        'Oro\Bundle\EmailBundle\Async\Topics::ADD_ASSOCIATION_TO_EMAILS' => 'Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationsTopic::getName',
        'Oro\Bundle\EmailBundle\Async\Topics::UPDATE_ASSOCIATIONS_TO_EMAILS' => 'Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailAssociationsTopic::getName',
        'Oro\Bundle\EmailBundle\Async\Topics::UPDATE_EMAIL_OWNER_ASSOCIATION' => 'Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationTopic::getName',
        'Oro\Bundle\EmailBundle\Async\Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS' => 'Oro\Bundle\EmailBundle\Async\Topic\UpdateEmailOwnerAssociationsTopic::getName',
        'Oro\Bundle\EmailBundle\Async\Topics::SYNC_EMAIL_SEEN_FLAG' => 'Oro\Bundle\EmailBundle\Async\Topic\SyncEmailSeenFlagTopic::getName',
        'Oro\Bundle\EmailBundle\Async\Topics::PURGE_EMAIL_ATTACHMENTS' => 'Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsTopic::getName',
        'Oro\Bundle\EmailBundle\Async\Topics::PURGE_EMAIL_ATTACHMENTS_BY_IDS' => 'Oro\Bundle\EmailBundle\Async\Topic\PurgeEmailAttachmentsByIdsTopic::getName',
    ]);

    // Removed Oro\Bundle\NotificationBundle\Async\Topics,
    // use getName() of corresponding topic class from Oro\Bundle\NotificationBundle\Async\Topic namespace instead.
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\Bundle\NotificationBundle\Async\Topics::SEND_NOTIFICATION_EMAIL' => 'Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTopic::getName',
        'Oro\Bundle\NotificationBundle\Async\Topics::SEND_MASS_NOTIFICATION_EMAIL' => 'Oro\Bundle\NotificationBundle\Async\Topic\SendMassEmailNotificationTopic::getName',
    ]);

    // Removed Oro\Bundle\ImapBundle\Async\Topics,
    // use getName() of corresponding topic class from Oro\Bundle\ImapBundle\Async\Topic namespace instead.
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\Bundle\ImapBundle\Async\Topics::CLEAR_INACTIVE_MAILBOX' => 'Oro\Bundle\ImapBundle\Async\Topic\ClearInactiveMailboxTopic::getName',
        'Oro\Bundle\ImapBundle\Async\Topics::SYNC_EMAIL' => 'Oro\Bundle\ImapBundle\Async\Topic\SyncEmailTopic::getName',
        'Oro\Bundle\ImapBundle\Async\Topics::SYNC_EMAILS' => 'Oro\Bundle\ImapBundle\Async\Topic\SyncEmailsTopic::getName',
    ]);

    // Removed Oro\Component\MessageQueue\Job\Topics,
    // use getName() of corresponding topic class from Oro\Component\MessageQueue\Job\Topic namespace instead.
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\Component\MessageQueue\Job\Topics::CALCULATE_ROOT_JOB_STATUS' => 'Oro\Component\MessageQueue\Job\Topic\CalculateRootJobStatusTopic::getName',
        'Oro\Component\MessageQueue\Job\Topics::ROOT_JOB_STOPPED' => 'Oro\Component\MessageQueue\Job\Topic\RootJobStoppedTopic::getName',
    ]);

    // Removed Oro\Bundle\TranslationBundle\Async\Topics,
    // use getName() of corresponding topic class from Oro\Bundle\TranslationBundle\Async\Topic namespace instead.
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\Bundle\TranslationBundle\Async\Topics::JS_TRANSLATIONS_DUMP' => 'Oro\Bundle\TranslationBundle\Async\Topic\DumpJsTranslationsTopic::getName',
    ]);

    // Removed Oro\Bundle\SearchBundle\Async\Topics,
    // use getName() of corresponding topic class from Oro\Bundle\SearchBundle\Async\Topic namespace instead.
    $rectorConfig->ruleWithConfiguration(ClassConstantToStaticMethodCallRector::class, [
        'Oro\Bundle\SearchBundle\Async\Topics::INDEX_ENTITY' => 'Oro\Bundle\SearchBundle\Async\Topic\IndexEntityTopic::getName',
        'Oro\Bundle\SearchBundle\Async\Topics::INDEX_ENTITY_TYPE' => 'Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByTypeTopic::getName',
        'Oro\Bundle\SearchBundle\Async\Topics::INDEX_ENTITY_BY_RANGE' => 'Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByRangeTopic::getName',
        'Oro\Bundle\SearchBundle\Async\Topics::INDEX_ENTITIES' => 'Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic::getName',
        'Oro\Bundle\SearchBundle\Async\Topics::REINDEX' => 'Oro\Bundle\SearchBundle\Async\Topic\ReindexTopic::getName',
    ]);
};
