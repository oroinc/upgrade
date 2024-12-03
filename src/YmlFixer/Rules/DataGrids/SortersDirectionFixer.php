<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Replace direction values in the sorters sections
 */
class SortersDirectionFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        if (!$this->isProcessable($config)) {
            return;
        }

        foreach (array_keys($config[Keys::DATAGRIDS]) as $datagrid) {
            if (!$this->areSortersProcessable($config, $datagrid)) {
                continue;
            }

            $sorters = $config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS];
            $directionReplaceCallback = function ($value) {
                return str_replace(
                    [
                        '%oro_datagrid.extension.orm_sorter.class%::DIRECTION_ASC',
                        '%oro_datagrid.extension.orm_sorter.class%::DIRECTION_DESC',
                    ],
                    [
                        'ASC',
                        'DESC',
                    ],
                    $value
                );
            };
            $this->rename($sorters, $directionReplaceCallback);
            $config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS] = $sorters;
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return "**/Resources/**/datagrids.yml";
    }

    private function isProcessable(array $config): bool
    {
        return array_key_exists(Keys::DATAGRIDS, $config) && is_array($config[Keys::DATAGRIDS]);
    }

    private function areSortersProcessable(array $config, string $datagrid): bool
    {
        return array_key_exists(Keys::SORTERS, $config[Keys::DATAGRIDS][$datagrid])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS]);
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
