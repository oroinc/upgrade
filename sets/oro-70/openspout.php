<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\OpenSpout\ReplaceReaderFactoryWithDirectInstantiationRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\OpenSpout\ReplaceWriterFactoryWithDirectInstantiationRector;
use Rector\Config\RectorConfig;

/**
 * Rule set to simplify the replacement of the box/spout with the openspout/openspout package
 */
return static function (RectorConfig $rectorConfig): void {
    //Replace factory`s createFromType method calls with direct instantiation
    $rectorConfig->rule(ReplaceReaderFactoryWithDirectInstantiationRector::class);
    $rectorConfig->rule(ReplaceWriterFactoryWithDirectInstantiationRector::class);

    $rectorConfig->ruleWithConfiguration(RenameNamespaceRector::class, [
        'Box\\Spout' => 'OpenSpout',
    ]);
};
