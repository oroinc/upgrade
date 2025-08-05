<?php

namespace Oro\UpgradeToolkit\YmlFixer\Config;

use Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids\AclResourcePlaceFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids\EnabledConfigKeyFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids\SkipAclCheckOptionFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids\SortersDirectionFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\DumpedThemes\DataGrid\DataGridEnumValueProviderRenameFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\DumpedThemes\DataGrid\DataGridIdentityReplaceFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\DumpedThemes\DataGrid\DataGridQueryFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Processes\ProcessesEnumIdentifierFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Processes\ProcessesExtendEntityReplaceFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Processes\ProcessesIdentityReplaceFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Routing\RoutingTypeFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Search\RemoveTitleFieldsFromSearchYamlFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Services\RenameClassFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Services\RenameEnumValueProviderServiceArgumentFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Services\RenameServiceFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Services\RenameSessionServiceArgumentFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Services\ServicesNamespaceCallsToCachePoolTagsFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Services\ServiceTagsPriorityFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Workflows\WorkflowsEnumIdentifierFixer;
use Oro\UpgradeToolkit\YmlFixer\Rules\Workflows\WorkflowsExcludedValuesFixer;
use Oro\UpgradeToolkit\YmlFixer\Visitor\YmlFileVisitor;

/**
 * Config returns lists of rules and visitors that should be applied to each .yml file
 */
final class Config
{
    public function getRules(): array
    {
        return [
            RenameServiceFixer::class,
            // Uncomment when the oro-70 ruleset will be finalized
            // RenameClassFixer::class,
            ServicesNamespaceCallsToCachePoolTagsFixer::class,
            ServiceTagsPriorityFixer::class,
            SortersDirectionFixer::class,
            EnabledConfigKeyFixer::class,
            SkipAclCheckOptionFixer::class,
            AclResourcePlaceFixer::class,
            RemoveTitleFieldsFromSearchYamlFixer::class,
            DataGridQueryFixer::class,
            DataGridEnumValueProviderRenameFixer::class,
            DataGridIdentityReplaceFixer::class,
            RenameSessionServiceArgumentFixer::class,
            RenameEnumValueProviderServiceArgumentFixer::class,
            ProcessesIdentityReplaceFixer::class,
            ProcessesEnumIdentifierFixer::class,
            ProcessesExtendEntityReplaceFixer::class,
            WorkflowsEnumIdentifierFixer::class,
            WorkflowsExcludedValuesFixer::class,
            // Uncomment when the oro-70 ruleset will be finalized
            // RoutingTypeFixer::class,
        ];
    }

    public function getVisitors(): array
    {
        return [
            YmlFileVisitor::class,
        ];
    }
}
