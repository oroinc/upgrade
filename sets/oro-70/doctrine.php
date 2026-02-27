<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\MethodCallReplace;
use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\AttributeArgReplace;
use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\MethodCallArgReplace;
use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\PropertyFetchWithConstructArgReplace;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\Doctrine\AddTypeToSetParameterRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\Doctrine\ReplaceUseResultCacheRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\Doctrine\ReplaceUuidGenerationStrategyRector;
use Oro\UpgradeToolkit\Rector\Rules\Renaming\Method\OroRenameMethodRector;
use Oro\UpgradeToolkit\Rector\Rules\Replace\ReplaceArgInMethodCallRector;
use Oro\UpgradeToolkit\Rector\Rules\Replace\ReplaceAttributeAgrRector;
use Oro\UpgradeToolkit\Rector\Rules\Replace\ReplacePropertyFetchWithConstructArgRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Renaming\ValueObject\RenameClassAndConstFetch;
use Rector\Renaming\ValueObject\RenameClassConstFetch;

return static function (RectorConfig $rectorConfig): void {
    // Help Rector resolve ManagerRegistry::getConnection() as Doctrine\DBAL\Connection
    // so that RenameMethodRector rules fire on chained calls like $doctrine->getConnection()->getWrappedConnection()
    $rectorConfig->phpstanConfig(__DIR__ . '/phpstan-for-rector.neon');

    // PDO constants are no longer part of DBAL API
    // Use Doctrine\DBAL\ParameterType instead
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassAndConstFetch(
            oldClass: 'PDO',
            oldConstant: 'PARAM_STR',
            newClass: 'Doctrine\\DBAL\\ParameterType',
            newConstant: 'STRING'
        ),
        new RenameClassAndConstFetch(
            oldClass: 'PDO',
            oldConstant: 'PARAM_INT',
            newClass: 'Doctrine\\DBAL\\ParameterType',
            newConstant: 'INTEGER'
        ),
        new RenameClassAndConstFetch(
            oldClass: 'PDO',
            oldConstant: 'PARAM_BOOL',
            newClass: 'Doctrine\\DBAL\\ParameterType',
            newConstant: 'BOOLEAN'
        ),
        new RenameClassAndConstFetch(
            oldClass: 'PDO',
            oldConstant: 'PARAM_NULL',
            newClass: 'Doctrine\\DBAL\\ParameterType',
            newConstant: 'NULL'
        ),
        new RenameClassAndConstFetch(
            oldClass: 'PDO',
            oldConstant: 'PARAM_LOB',
            newClass: 'Doctrine\\DBAL\\ParameterType',
            newConstant: 'LARGE_OBJECT'
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(ReplacePropertyFetchWithConstructArgRector::class, [
        new PropertyFetchWithConstructArgReplace(
            class: 'Doctrine\\DBAL\\Schema\\TableDiff'
        )
    ]);

    $rectorConfig->ruleWithConfiguration(ReplaceArgInMethodCallRector::class, [
        new MethodCallArgReplace(
            class: 'Doctrine\\DBAL\\Schema\\Table',
            method: 'addColumn',
            argName: 'typeName',
            oldValue: 'json_array',
            newValue: 'json',
        ),
        // Options Arg
        new MethodCallArgReplace(
            class: 'Doctrine\\DBAL\\Schema\\Table',
            method: 'addColumn',
            argName: 'options',
            oldValue: ['comment' => '(DC2Type:json_array)'],
            newValue: ['comment' => '(DC2Type:json)'],
        ),
        new MethodCallArgReplace(
            class: 'Doctrine\\DBAL\\Schema\\Table',
            method: 'addColumn',
            argName: 'options',
            oldValue: ['comment' => '(DC2Type:json_array)', 'notnull' => false],
            newValue: ['comment' => '(DC2Type:json)', 'notnull' => false],
        ),
        new MethodCallArgReplace(
            class: 'Doctrine\\DBAL\\Schema\\Table',
            method: 'addColumn',
            argName: 'options',
            oldValue: [ 'notnull' => false, 'comment' => '(DC2Type:json_array)'],
            newValue: [ 'notnull' => false, 'comment' => '(DC2Type:json)'],
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(ReplaceArgInMethodCallRector::class, [
        new MethodCallArgReplace(
            class: 'Doctrine\\ORM\\QueryBuilder',
            method: 'setFirstResult',
            argName: 'firstResult',
            oldValue: null,
            newValue: 0,
        )
    ]);

    $rectorConfig->ruleWithConfiguration(
        ReplaceAttributeAgrRector::class,
        [
            new AttributeArgReplace(
                tag: 'ORM\Column',
                class: 'Doctrine\\ORM\\Mapping\\Column',
                argName: 'type',
                oldValue: 'json_array',
                newValue: 'json',
            ),
            new AttributeArgReplace(
                tag: 'Column',
                class: 'Doctrine\\ORM\\Mapping\\Column',
                argName: 'type',
                oldValue: 'json_array',
                newValue: 'json',
            )
        ]
    );

    //Rename Doctrine DBAL classes
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Doctrine\\DBAL\\Platforms\\MySqlPlatform' => 'Doctrine\\DBAL\\Platforms\\MySQLPlatform',
    ]);
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Doctrine\\DBAL\\DBALException' => 'Doctrine\\DBAL\\Exception',
    ]);

    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Doctrine\\DBAL\\Platforms\\PostgreSQL92Platform' => 'Doctrine\\DBAL\\Platforms\\PostgreSQLPlatform',
    ]);

    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Doctrine\\DBAL\\Types\\Type' => 'Doctrine\\DBAL\\Types\\Types',
    ]);

    // Replace const fetch
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        // Types::JSON_ARRAY -> Types::JSON
        new RenameClassConstFetch(
            'Doctrine\\DBAL\\Types\\Types',
            'JSON_ARRAY',
            'JSON'
        ),
        new RenameClassConstFetch(
            'Doctrine\\DBAL\\Types\\Type',
            'JSON_ARRAY',
            'JSON'
        ),
        // Types::DATETIME -> Types::DATETIME_MUTABLE
        new RenameClassConstFetch(
            'Doctrine\\DBAL\\Types\\Types',
            'DATETIME',
            'DATETIME_MUTABLE'
        ),
        new RenameClassConstFetch(
            'Doctrine\\DBAL\\Types\\Type',
            'DATETIME',
            'DATETIME_MUTABLE'
        ),
    ]);

    // Rename methods
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Doctrine\\DBAL\\Connection',
            'getSchemaManager',
            'createSchemaManager'
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Doctrine\\DBAL\\Connection',
            'getWrappedConnection',
            'getNativeConnection'
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Doctrine\\DBAL\\Schema\\AbstractSchemaManager',
            'listTableDetails',
            'introspectTable'
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Doctrine\\DBAL\\Schema\\AbstractSchemaManager',
            'createSchema',
            'introspectSchema'
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Doctrine\\ORM\\Query',
            'iterate',
            'toIterable'
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Doctrine\\ORM\\Query\\TreeWalkerAdapter',
            '_getQueryComponents',
            'getQueryComponents'
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Doctrine\\ORM\\Event\\PreUpdateEventArgs',
            'getEntity',
            'getObject'
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Doctrine\\ORM\\Configuration',
            'getResultCacheImpl',
            'getResultCache'
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            class: 'Doctrine\\DBAL\\Schema\\Table',
            oldMethod: 'changeColumn',
            newMethod: 'modifyColumn',
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(OroRenameMethodRector::class, [
        new MethodCallReplace(
            class: 'Doctrine\\DBAL\\Schema\\Table',
            oldMethod: 'getPrimaryKeyColumns',
            newMethod: 'getPrimaryKey',
            chainedMethods: ['getColumns']
        ),
    ]);

    // DBAL Connection method renames (deprecated in DBAL 2.x, removed in 4.x)
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename('Doctrine\\DBAL\\Connection', 'fetchAll', 'fetchAllAssociative'),
        new MethodCallRename('Doctrine\\DBAL\\Connection', 'fetchAssoc', 'fetchAssociative'),
        new MethodCallRename('Doctrine\\DBAL\\Connection', 'fetchArray', 'fetchNumeric'),
        new MethodCallRename('Doctrine\\DBAL\\Connection', 'fetchColumn', 'fetchOne'),
    ]);

    // Statement method renames
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename('Doctrine\\DBAL\\Statement', 'fetchAll', 'fetchAllAssociative'),
        new MethodCallRename('Doctrine\\DBAL\\Statement', 'fetchAssoc', 'fetchAssociative'),
        new MethodCallRename('Doctrine\\DBAL\\Statement', 'fetchColumn', 'fetchOne'),
        new MethodCallRename('Doctrine\\DBAL\\Statement', 'fetch', 'fetchAssociative'),
    ]);

    $rectorConfig->rule(AddTypeToSetParameterRector::class);
    $rectorConfig->rule(ReplaceUseResultCacheRector::class);
    $rectorConfig->rule(ReplaceUuidGenerationStrategyRector::class);
};
