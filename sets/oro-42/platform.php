<?php

use Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Renaming\ValueObject\RenameClassAndConstFetch;

return static function (RectorConfig $rectorConfig): void {
    // v4.1

    // The following classes were moved from Oro\Bundle\ApiBundle\Config namespace
    // to Oro\Bundle\ApiBundle\Config\Extension:
    // ConfigExtensionInterface
    // AbstractConfigExtension
    // ConfigExtensionRegistry
    // FeatureConfigurationExtension
    // ActionsConfigExtension
    // FiltersConfigExtension
    // SortersConfigExtension
    // SubresourcesConfigExtension
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\ApiBundle\Config\ConfigExtensionInterface' => 'Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionInterface',
        'Oro\Bundle\ApiBundle\Config\AbstractConfigExtension' => 'Oro\Bundle\ApiBundle\Config\Extension\AbstractConfigExtension',
        'Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry' => 'Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry',
        'Oro\Bundle\ApiBundle\Config\FeatureConfigurationExtension' => 'Oro\Bundle\ApiBundle\Config\Extension\FeatureConfigurationExtension',
        'Oro\Bundle\ApiBundle\Config\ActionsConfigExtension' => 'Oro\Bundle\ApiBundle\Config\Extension\ActionsConfigExtension',
        'Oro\Bundle\ApiBundle\Config\FiltersConfigExtension' => 'Oro\Bundle\ApiBundle\Config\Extension\FiltersConfigExtension',
        'Oro\Bundle\ApiBundle\Config\SortersConfigExtension' => 'Oro\Bundle\ApiBundle\Config\Extension\SortersConfigExtension',
        'Oro\Bundle\ApiBundle\Config\SubresourcesConfigExtension' => 'Oro\Bundle\ApiBundle\Config\Extension\SubresourcesConfigExtension',
    ]);

    // The following classes were moved from Oro\Bundle\ApiBundle\Config namespace
    // to Oro\Bundle\ApiBundle\Config\Extra:
    // ConfigExtraInterface
    // ConfigExtraSectionInterface
    // ConfigExtraCollection
    // CustomizeLoadedDataConfigExtra
    // DataTransformersConfigExtra
    // DescriptionsConfigExtra
    // EntityDefinitionConfigExtra
    // ExpandRelatedEntitiesConfigExtra
    // FilterFieldsConfigExtra
    // FilterIdentifierFieldsConfigExtra
    // FiltersConfigExtra
    // MaxRelatedEntitiesConfigExtra
    // MetaPropertiesConfigExtra
    // RootPathConfigExtra
    // SortersConfigExtra
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\ApiBundle\Config\ConfigExtraInterface' => 'Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface',
        'Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface' => 'Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraSectionInterface',
        'Oro\Bundle\ApiBundle\Config\ConfigExtraCollection' => 'Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraCollection',
        'Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\CustomizeLoadedDataConfigExtra',
        'Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\DataTransformersConfigExtra',
        'Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra',
        'Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra',
        'Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra',
        'Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra',
        'Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra',
        'Oro\Bundle\ApiBundle\Config\FiltersConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra',
        'Oro\Bundle\ApiBundle\Config\MaxRelatedEntitiesConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\MaxRelatedEntitiesConfigExtra',
        'Oro\Bundle\ApiBundle\Config\MetaPropertiesConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\MetaPropertiesConfigExtra',
        'Oro\Bundle\ApiBundle\Config\RootPathConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\RootPathConfigExtra',
        'Oro\Bundle\ApiBundle\Config\SortersConfigExtra' => 'Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra',
    ]);

    // The following classes were moved from Oro\Bundle\ApiBundle\Config namespace
    // to Oro\Bundle\ApiBundle\Config\Loader:
    // ConfigLoaderInterface
    // AbstractConfigLoader
    // ConfigLoaderFactory
    // ConfigLoaderFactoryAwareInterface
    // ActionsConfigLoader
    // EntityDefinitionConfigLoader
    // EntityDefinitionFieldConfigLoader
    // FiltersConfigLoader
    // SortersConfigLoader
    // StatusCodesConfigLoader
    // SubresourcesConfigLoader
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\ApiBundle\Config\ConfigLoaderInterface' => 'Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderInterface',
        'Oro\Bundle\ApiBundle\Config\AbstractConfigLoader' => 'Oro\Bundle\ApiBundle\Config\Loader\AbstractConfigLoader',
        'Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory' => 'Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory',
        'Oro\Bundle\ApiBundle\Config\ConfigLoaderFactoryAwareInterface' => 'Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactoryAwareInterface',
        'Oro\Bundle\ApiBundle\Config\ActionsConfigLoader' => 'Oro\Bundle\ApiBundle\Config\Loader\ActionsConfigLoader',
        'Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigLoader' => 'Oro\Bundle\ApiBundle\Config\Loader\EntityDefinitionConfigLoader',
        'Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfigLoader' => 'Oro\Bundle\ApiBundle\Config\Loader\EntityDefinitionFieldConfigLoader',
        'Oro\Bundle\ApiBundle\Config\FiltersConfigLoader' => 'Oro\Bundle\ApiBundle\Config\Loader\FiltersConfigLoader',
        'Oro\Bundle\ApiBundle\Config\SortersConfigLoader' => 'Oro\Bundle\ApiBundle\Config\Loader\SortersConfigLoader',
        'Oro\Bundle\ApiBundle\Config\StatusCodesConfigLoader' => 'Oro\Bundle\ApiBundle\Config\Loader\StatusCodesConfigLoader',
        'Oro\Bundle\ApiBundle\Config\SubresourcesConfigLoader' => 'Oro\Bundle\ApiBundle\Config\Loader\SubresourcesConfigLoader',
    ]);

    // The following classes were moved from Oro\Bundle\ApiBundle\Metadata namespace
    // to Oro\Bundle\ApiBundle\Metadata\Extra:
    // MetadataExtraInterface
    // MetadataExtraCollection
    // ActionMetadataExtra
    // HateoasMetadataExtra
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface' => 'Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface',
        'Oro\Bundle\ApiBundle\Metadata\MetadataExtraCollection' => 'Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraCollection',
        'Oro\Bundle\ApiBundle\Metadata\ActionMetadataExtra' => 'Oro\Bundle\ApiBundle\Metadata\Extra\ActionMetadataExtra',
        'Oro\Bundle\ApiBundle\Metadata\HateoasMetadataExtra' => 'Oro\Bundle\ApiBundle\Metadata\Extra\HateoasMetadataExtra',
    ]);

    // All processors from Oro\Bundle\ApiBundle\Processor\Config\GetConfig
    // and Oro\Bundle\ApiBundle\Processor\Config\Shared namespaces
    // were moved to Oro\Bundle\ApiBundle\Processor\GetConfig namespace.
    $rectorConfig->ruleWithConfiguration(RenameNamespaceRector::class, [
        'Oro\Bundle\ApiBundle\Processor\Config\GetConfig' => 'Oro\Bundle\ApiBundle\Processor\GetConfig',
        'Oro\Bundle\ApiBundle\Processor\Config\Shared' => 'Oro\Bundle\ApiBundle\Processor\GetConfig',
    ]);

    // The class ConfigProcessor was moved from Oro\Bundle\ApiBundle\Processor\Config namespace
    // to Oro\Bundle\ApiBundle\Processor\GetConfig namespace.
    // The class ConfigContext was moved from Oro\Bundle\ApiBundle\Processor\Config namespace
    // to Oro\Bundle\ApiBundle\Processor\GetConfig namespace.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\ApiBundle\Processor\Config\ConfigProcessor' => 'Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigProcessor',
        'Oro\Bundle\ApiBundle\Processor\Config\ConfigContext' => 'Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext',
    ]);

    // The constant SCOPE_KEY
    // in Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider
    // was replaced with ORGANIZATION.
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassAndConstFetch(
            'Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider',
            'SCOPE_KEY',
            'Oro\Bundle\OrganizationBundle\Provider\ScopeOrganizationCriteriaProvider',
            'ORGANIZATION'
        ),
    ]);

    // The method getCriteriaForCurrentScope()
    // in Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface
    // was replaced with getCriteriaValue().
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface',
            'getCriteriaForCurrentScope',
            'getCriteriaValue'
        ),
    ]);

    // The interface Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface
    // was renamed to Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface.
    // Also, methods getOrganizationContext and setOrganizationContext
    // were renamed to getOrganization and setOrganization.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface' => 'Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface',
    ]);
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface',
            'getOrganizationContext',
            'getOrganization'
        ),
        new MethodCallRename(
            'Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface',
            'setOrganizationContext',
            'setOrganization'
        ),
    ]);

    // The class Oro\Bundle\SecurityBundle\Exception\ForbiddenException was removed.
    // Use Symfony\Component\Security\Core\Exception\AccessDeniedException instead.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\SecurityBundle\Exception\ForbiddenException' => 'Symfony\Component\Security\Core\Exception\AccessDeniedException',
    ]);

    //The constant SCOPE_KEY in Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider was replaced with USER.
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassAndConstFetch(
            'Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider',
            'SCOPE_KEY',
            'Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider',
            'USER'
        ),
    ]);

    // The deprecated constant Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_PATH was removed.
    // Use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_PATH instead.
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassAndConstFetch(
            'Oro\Bundle\DataGridBundle\Datagrid\Builder',
            'DATASOURCE_PATH',
            'Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration',
            'DATASOURCE_PATH'
        ),
    ]);

    // The deprecated constant Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_TYPE_PATH was removed.
    // Use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_TYPE_PATH
    // and Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::getDatasourceType() instead.
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassAndConstFetch(
            'Oro\Bundle\DataGridBundle\Datagrid\Builder',
            'DATASOURCE_TYPE_PATH',
            'Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration',
            'DATASOURCE_TYPE_PATH'
        ),
    ]);

    // The deprecated constant Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_ACL_PATH was removed.
    // Use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::ACL_RESOURCE_PATH
    // and Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::getAclResource() instead.
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassAndConstFetch(
            'Oro\Bundle\DataGridBundle\Datagrid\Builder',
            'DATASOURCE_ACL_PATH',
            'Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration',
            'ACL_RESOURCE_PATH'
        ),
    ]);

    // The deprecated constant Oro\Bundle\DataGridBundle\Datagrid\Builder::BASE_DATAGRID_CLASS_PATH was removed.
    // Use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::BASE_DATAGRID_CLASS_PATH instead.
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassAndConstFetch(
            'Oro\Bundle\DataGridBundle\Datagrid\Builder',
            'BASE_DATAGRID_CLASS_PATH',
            'Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration',
            'BASE_DATAGRID_CLASS_PATH'
        ),
    ]);

    // The deprecated constant Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_SKIP_ACL_CHECK was removed.
    // Use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_SKIP_ACL_APPLY_PATH
    // and Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::isDatasourceSkipAclApply() instead.
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassAndConstFetch(
            'Oro\Bundle\DataGridBundle\Datagrid\Builder',
            'DATASOURCE_SKIP_ACL_CHECK',
            'Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration',
            'DATASOURCE_SKIP_ACL_APPLY_PATH'
        ),
    ]);

    // The deprecated constant Oro\Bundle\DataGridBundle\Datagrid\Builder::DATASOURCE_SKIP_COUNT_WALKER_PATH was removed.
    // Use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration::DATASOURCE_SKIP_COUNT_WALKER_PATH instead.
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassAndConstFetch(
            'Oro\Bundle\DataGridBundle\Datagrid\Builder',
            'DATASOURCE_SKIP_COUNT_WALKER_PATH',
            'Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration',
            'DATASOURCE_SKIP_COUNT_WALKER_PATH'
        ),
    ]);

    // The deprecated class Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent was removed.
    // It was replaced with Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent' => 'Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent',
    ]);

    // The deprecated class Oro\Bundle\EntityConfigBundle\Event\FlushConfigEvent was removed.
    // It was replaced with Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EntityConfigBundle\Event\FlushConfigEvent' => 'Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent',
    ]);

    // The deprecated method Oro\Component\Math\BigDecimal::withScale() was removed.
    // Use toScale() method instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Component\Math\BigDecimal',
            'withScale',
            'toScale'
        ),
    ]);

    // The deprecated method Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension::put() was removed.
    // Use set() method instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension',
            'put',
            'set'
        ),
    ]);

    // The deprecated Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpEntityNameParameterFilter class was removed.
    // Use Oro\Bundle\SoapBundle\Request\Parameters\Filter\EntityClassParameterFilter instead.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpEntityNameParameterFilter' => 'Oro\Bundle\SoapBundle\Request\Parameters\Filter\EntityClassParameterFilter',
    ]);

    // The deprecated method Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface::getGlobalOwnerFieldName() was removed.
    // Use getOrganizationFieldName() method instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface',
            'getGlobalOwnerFieldName',
            'getOrganizationFieldName'
        ),
    ]);

    // v4.2.0

    // The class Oro\Bundle\QueryDesignerBundle\QueryDesigner\FilterProcessor
    // was renamed to Oro\Bundle\SegmentBundle\Query\FilterProcessor.
    $rectorConfig->ruleWithConfiguration(RenameNamespaceRector::class, [
        'Oro\Bundle\QueryDesignerBundle\QueryDesigner' => 'Oro\Bundle\SegmentBundle\Query',
    ]);

    // The following changes were done in
    // the Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider class:
    // the method getPermissionCategories was renamed to getCategories
    // the method getTabList was renamed to getTabIds
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider',
            'getPermissionCategories',
            'getCategories'
        ),
        new MethodCallRename(
            'Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider',
            'getTabList',
            'getTabIds'
        ),
    ]);

    // v4.2.4

    // Removed Oro\Bundle\EmailBundle\Mailer\DirectMailer,
    // use Oro\Bundle\EmailBundle\Mailer\Mailer instead.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EmailBundle\Mailer\DirectMailer' => 'Oro\Bundle\EmailBundle\Mailer\Mailer'
    ]);

    // Removed Oro\Bundle\EmailBundle\Mailer\Processor,
    // use Oro\Bundle\EmailBundle\Sender\EmailModelSender instead.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EmailBundle\Mailer\Processor' => 'Oro\Bundle\EmailBundle\Sender\EmailModelSender'
    ]);

    // Removed Oro\Bundle\EmailBundle\Model\DTO\EmailAddressDTO,
    // use \Oro\Bundle\EmailBundle\Model\Recipient instead.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\EmailBundle\Model\DTO\EmailAddressDTO' => 'Oro\Bundle\EmailBundle\Model\Recipient'
    ]);

    // Removed Oro\Bundle\ImapBundle\EventListener\SendEmailTransportListener
    // in favor of Oro\Bundle\ImapBundle\EventListener\SetUserEmailOriginTransportListener.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\ImapBundle\EventListener\SendEmailTransportListener' => 'Oro\Bundle\ImapBundle\EventListener\SetUserEmailOriginTransportListener'
    ]);

    // Removed Oro\Bundle\LoggerBundle\Mailer\NoRecipientPlugin
    // in favor of Oro\Bundle\LoggerBundle\Monolog\ErrorLogNotificationHandlerWrapper.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\LoggerBundle\Mailer\NoRecipientPlugin' => 'Oro\Bundle\LoggerBundle\Monolog\ErrorLogNotificationHandlerWrapper'
    ]);

    // Removed Oro\Bundle\LoggerBundle\Mailer\MessageFactory
    // in favor of Oro\Bundle\LoggerBundle\Monolog\EmailFactory\ErrorLogNotificationEmailFactory.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\LoggerBundle\Mailer\MessageFactory' => 'Oro\Bundle\LoggerBundle\Monolog\EmailFactory\ErrorLogNotificationEmailFactory'
    ]);

    // Removed Oro\Bundle\NotificationBundle\Async\SendEmailMessageProcessor.
    // Use Oro\Bundle\NotificationBundle\Async\SendEmailNotificationProcessor
    // and Oro\Bundle\NotificationBundle\Async\SendEmailNotificationTemplateProcessor instead.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\NotificationBundle\Async\SendEmailMessageProcessor' => 'Oro\Bundle\NotificationBundle\Async\SendEmailNotificationProcessor'
    ]);

    // Removed Oro\Bundle\NotificationBundle\Async\SendMassEmailMessageProcessor
    // in favor of Oro\Bundle\NotificationBundle\Async\SendEmailNotificationProcessor.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\NotificationBundle\Async\SendMassEmailMessageProcessor' => 'Oro\Bundle\NotificationBundle\Async\SendEmailNotificationProcessor'
    ]);

    // Removed Oro\Bundle\NotificationBundle\Mailer\MassEmailDirectMailer.
    // Use Oro\Bundle\NotificationBundle\Mailer\MassNotificationsMailer instead.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Bundle\NotificationBundle\Mailer\MassEmailDirectMailer' => 'Oro\Bundle\NotificationBundle\Mailer\MassNotificationsMailer'
    ]);

    // Removed Oro\Component\MessageQueue\Client\ContainerAwareMessageProcessorRegistry,
    // use instead Oro\Component\MessageQueue\Client\MessageProcessorRegistry service locator.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Oro\Component\MessageQueue\Client\ContainerAwareMessageProcessorRegistry' => 'Oro\Component\MessageQueue\Client\MessageProcessorRegistry'
    ]);

    // Removed Oro\Component\MessageQueue\Consumption\Context::getMessageProcessor()
    // and Oro\Component\MessageQueue\Consumption\Context::setMessageProcessor(),
    // use instead Oro\Component\MessageQueue\Consumption::getMessageProcessorName(),
    // Oro\Component\MessageQueue\Consumption::setMessageProcessorName().
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Component\MessageQueue\Consumption\Context',
            'getMessageProcessor',
            'getMessageProcessorName'
        ),
        new MethodCallRename(
            'Oro\Component\MessageQueue\Consumption\Context',
            'setMessageProcessor',
            'setMessageProcessorName'
        )
    ]);

    // Removed Oro\Component\MessageQueue\Log\ConsumerState::getMessageProcessor()
    // and Oro\Component\MessageQueue\Log\ConsumerState::setMessageProcessor(),
    // use instead Oro\Component\MessageQueue\Log\ConsumerState::getMessageProcessorName(),
    // Oro\Component\MessageQueue\Log\ConsumerState::setMessageProcessorName().
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Component\MessageQueue\Log\ConsumerState',
            'getMessageProcessor',
            'getMessageProcessorName'
        ),
        new MethodCallRename(
            'Oro\Component\MessageQueue\Log\ConsumerState',
            'setMessageProcessor',
            'setMessageProcessorName'
        )
    ]);

    // Removed Oro\Component\MessageQueue\Client\Meta\TopicMeta::getDescription(),
    // use Oro\Component\MessageQueue\Client\Meta\TopicDescriptionProvider::getTopicDescription() instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Component\MessageQueue\Client\Meta\TopicMeta',
            'getDescription',
            'getTopicDescription'
        ),
    ]);

    // Removed \Oro\Component\MessageQueue\Log\MessageProcessorClassProvider::getMessageProcessorClass(),
    // use \Oro\Component\MessageQueue\Log\MessageProcessorClassProvider::getMessageProcessorClassByName()
    // instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Component\MessageQueue\Log\MessageProcessorClassProvider',
            'getMessageProcessorClass',
            'getMessageProcessorClassByName'
        ),
    ]);
};
