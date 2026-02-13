<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\MethodCallReplace;
use Oro\UpgradeToolkit\Rector\Rules\Renaming\Method\OroRenameMethodRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\ValueObject\MethodCallRename;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(OroRenameMethodRector::class, [
        new MethodCallRename(
            'Test\SimpleService',
            'oldMethod',
            'newMethod'
        ),
        new MethodCallRename(
            'Test\ClassWithMethod',
            'oldClassMethod',
            'newClassMethod'
        ),
        new MethodCallRename(
            'Test\InterfaceWithMethod',
            'oldInterfaceMethod',
            'newInterfaceMethod'
        ),
        new MethodCallRename(
            'Test\TraitWithMethod',
            'oldTraitMethod',
            'newTraitMethod'
        ),
    ]);

    $rectorConfig->ruleWithConfiguration(OroRenameMethodRector::class, [
        new MethodCallReplace(
            'Test\ChainedService',
            'getConfiguration',
            'config',
            ['all']
        ),
        new MethodCallReplace(
            'Test\MultiChainService',
            'getData',
            'data',
            ['get', 'toArray']
        ),
        new MethodCallReplace(
            'Test\StaticService',
            'getInstance',
            'create',
            ['initialize']
        ),
    ]);
};
