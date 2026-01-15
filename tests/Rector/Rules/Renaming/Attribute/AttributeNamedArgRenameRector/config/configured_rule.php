<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\RenameAttributeNamedArg;
use Oro\UpgradeToolkit\Rector\Rules\Renaming\Attribute\AttributeNamedArgRenameRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(
        AttributeNamedArgRenameRector::class,
        [
            new RenameAttributeNamedArg(
                'Acl',
                'Oro\Bundle\SecurityBundle\Attribute\Acl',
                'group_name',
                'groupName'
            ),
            new RenameAttributeNamedArg(
                'AclAncestor',
                'Oro\Bundle\SecurityBundle\Attribute\AclAncestor',
                'acl_annotation_id',
                'value'
            ),
        ]
    );
};
