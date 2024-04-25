<?php

use Oro\Rector\Rules\Oro60\Annotation\MinimizeAnnotationRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    // Transform multi-lined annotations to single-lined excluding extra chars.
    // This needed to avoid mistakes while annotations parsing on the next steps.
    $rectorConfig->ruleWithConfiguration(
        MinimizeAnnotationRector::class,
        [
            'Config',
            'ConfigField',
            'Acl',
            'AclAncestor',
            'CsrfProtection',
            'TitleTemplate',
            'Layout',
            'DiscriminatorValue',
            'Help',
            'NamePrefix',
            'RouteResource',
        ]
    );
};
