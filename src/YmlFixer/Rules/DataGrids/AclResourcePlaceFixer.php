<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * The source / acl_resource datagrid option was removed.
 * Use the acl_resource option instead.
 */
class AclResourcePlaceFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        if (!$this->isProcessable($config)) {
            return;
        }

        foreach ($config[Keys::DATAGRIDS] as $key => $datagrid) {
            if (is_array($datagrid)
                && array_key_exists(Keys::SOURCE, $datagrid)
                && array_key_exists(Keys::ACL_RESOURCE, $datagrid[Keys::SOURCE])
            ) {
                $value = $datagrid[Keys::SOURCE][Keys::ACL_RESOURCE];
                $config[Keys::DATAGRIDS][$key][Keys::ACL_RESOURCE] = $value;
                unset($config[Keys::DATAGRIDS][$key][Keys::SOURCE][Keys::ACL_RESOURCE]);

                if (empty($config[Keys::DATAGRIDS][$key][Keys::SOURCE])) {
                    unset($config[Keys::DATAGRIDS][$key][Keys::SOURCE]);
                }
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
}
