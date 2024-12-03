<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Changes configuration variable from enabled to renderable
 */
class EnabledConfigKeyFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        if (!$this->isProcessable($config)) {
            return;
        }

        foreach ($config[Keys::DATAGRIDS] as $key => $datagrid) {
            if (is_array($datagrid)) {
                $this->renameEnabledToRenderable($datagrid);
                $config[Keys::DATAGRIDS][$key] = $datagrid;
            }
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

    private function renameEnabledToRenderable(array &$array): void
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->renameEnabledToRenderable($value);
            } elseif (is_bool($value) && Keys::ENABLED === $key) {
                unset($array[$key]);
                $array[Keys::RENDERABLE] = $value;
            }
        }
    }
}
