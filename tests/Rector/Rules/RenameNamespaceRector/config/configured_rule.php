<?php

declare(strict_types=1);

use Oro\Rector\Rules\Namespace\RenameNamespaceRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RenameNamespaceRector::class, [
        'Old\Abstract\Controller\Namespace' => 'New\Abstract\Controller\Namespace',
    ]);
};
