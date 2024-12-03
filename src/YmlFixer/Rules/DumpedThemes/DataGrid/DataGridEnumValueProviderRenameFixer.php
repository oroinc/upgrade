<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\DumpedThemes\DataGrid;

use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Replaces usage of the old enum_value_provider service
 * to the new one enum_options_provider
 * by renaming the service call statement
 */
class DataGridEnumValueProviderRenameFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        $serviceNameRename = function ($value) {
            return str_replace(
                '@oro_entity_extend.enum_value_provider->getEnumChoicesByCode(',
                '@oro_entity_extend.enum_options_provider->getEnumChoicesByCode(',
                $value
            );
        };

        $this->rename($config, $serviceNameRename);
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/ThemeDefault*Bundle/Resources/views/layouts/default_**/config/datagrids.yml';
    }

    private function rename(array &$array, callable $callback): void
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->rename($value, $callback);
            } elseif (is_string($value)) {
                $value = $callback($value);
            }
        }
    }
}
