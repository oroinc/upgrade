<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * The options / skip_acl_check datagrid option was removed.
 * Use the source / skip_acl_apply option instead.
 */
class SkipAclCheckOptionFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        if (!$this->isProcessable($config)) {
            return;
        }

        foreach ($config[Keys::DATAGRIDS] as $key => $datagrid) {
            if (is_array($datagrid)
                && array_key_exists(Keys::OPTIONS, $datagrid)
                && array_key_exists(Keys::SKIP_ACL_CHECK, $datagrid[Keys::OPTIONS])
            ) {
                $value = $datagrid[Keys::OPTIONS][Keys::SKIP_ACL_CHECK];
                $config[Keys::DATAGRIDS][$key][Keys::SOURCE][Keys::SKIP_ACL_APPLY] = $value;
                unset($config[Keys::DATAGRIDS][$key][Keys::OPTIONS][Keys::SKIP_ACL_CHECK]);

                if (empty($config[Keys::DATAGRIDS][$key][Keys::OPTIONS])) {
                    unset($config[Keys::DATAGRIDS][$key][Keys::OPTIONS]);
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
