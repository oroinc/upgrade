<?php

use Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Symfony\Set\SymfonySetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\ValueObject\AddReturnTypeDeclaration;
use Rector\ValueObject\Visibility;
use Rector\Visibility\Rector\ClassMethod\ChangeMethodVisibilityRector;
use Rector\Visibility\ValueObject\ChangeMethodVisibility;

return static function (RectorConfig $rectorConfig): void {
    // Bind custom Process Command class instead of default
    // to inject custom file processors into the Rector`s flow
    $rectorConfig->bind(
        \Rector\Console\Command\ProcessCommand::class,
        \Oro\UpgradeToolkit\Rector\Console\Command\ProcessCommand::class
    );

    $rectorConfig->import(__DIR__ . '/skip-list.php');

    $rectorConfig->sets([
        SymfonySetList::SYMFONY_50,
        SymfonySetList::SYMFONY_50_TYPES,
        SymfonySetList::SYMFONY_51,
        SymfonySetList::SYMFONY_52,
        SymfonySetList::SYMFONY_52_VALIDATOR_ATTRIBUTES,
        SymfonySetList::SYMFONY_53,
        SymfonySetList::SYMFONY_54,
    ]);

    $rectorConfig->import(__DIR__ . '/oro-51/api.php');
    $rectorConfig->import(__DIR__ . '/oro-51/cron-commands.php');
    $rectorConfig->import(__DIR__ . '/oro-51/entity-extend.php');
    $rectorConfig->import(__DIR__ . '/oro-51/mq-topics.php');

    // v5.0

    // Oro\Bundle\EntityBundle\Provider\EntityFieldProvider::getFields() was removed,
    // use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider::getEntityFields() instead.
    // Oro\Bundle\EntityBundle\Helper\FieldHelper::getFields() was removed,
    // use Oro\Bundle\EntityBundle\Helper\FieldHelper::getEntityFields() instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\EntityBundle\Provider\EntityFieldProvider',
            'getFields',
            'getEntityFields'
        ),
        new MethodCallRename(
            'Oro\Bundle\EntityBundle\Helper\FieldHelper',
            'getFields',
            'getEntityFields'
        ),
    ]);

    // v5.1

    $rectorConfig->ruleWithConfiguration(RenameNamespaceRector::class, [
        // Moved all ORM relates mocks and test cases to Testing component.
        // Old namespace for these classes was Oro\Component\TestUtils\ORM. New namespace is Oro\Component\Testing\Unit\ORM.
        'Oro\Component\TestUtils\ORM' => 'Oro\Component\Testing\Unit\ORM',
    ]);
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        // Oro\Bundle\FormBundle\Model\UpdateHandler has been removed. Use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade instead.
        'Oro\Bundle\FormBundle\Model\UpdateHandler' => 'Oro\Bundle\FormBundle\Model\UpdateHandlerFacade',
        // Removed Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder, use Oro\Component\Layout\LayoutContextStack instead.
        'Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder' => 'Oro\Component\Layout\LayoutContextStack',
        // Removed Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder, added \Oro\Bundle\NavigationBundle\Menu\MenuUpdateBuilder instead.
        'Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder' => 'Oro\Bundle\NavigationBundle\Menu\MenuUpdateBuilder',
        // The oro.cache.abstract abstract service is removed, use oro.data.cache instead, with the cache.pool tag and the namespace in a tag attribute.
        'Doctrine\Common\Cache\CacheProvider' => 'Symfony\Contracts\Cache\CacheInterface',
    ]);

    $rectorConfig->ruleWithConfiguration(AddReturnTypeDeclarationRector::class, [
        //Changed Oro\Bundle\AttachmentBundle\Manager\FileManager::getFileFromFileEntity return type to ?\SplFileInfo
        // to comply with Oro\Bundle\AttachmentBundle\Entity\File::$file property type.
        new AddReturnTypeDeclaration(
            'Oro\Bundle\AttachmentBundle\Manager\FileManager',
            'getFileFromFileEntity',
            new UnionType([new NullType(), new ObjectType('SplFileInfo')])
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(ChangeMethodVisibilityRector::class, [
        //Changed Oro\Bundle\AttachmentBundle\ImportExport\FileImportStrategyHelper::getFieldLabel visibility to public,
        // so it can be used for getting human-readable field names during import.
        new ChangeMethodVisibility(
            'Oro\Bundle\AttachmentBundle\ImportExport\FileImportStrategyHelper',
            'getFieldLabel',
            Visibility::PUBLIC
        ),
    ]);

    // Removed \Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent::setResultField, use \Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent::setResultFieldValue instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent',
            'setResultField',
            'setResultFieldValue'
        )
    ]);
};
