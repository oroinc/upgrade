<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Search;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Removes title_fields from search configs
 */
class RemoveTitleFieldsFromSearchYamlFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        if ($this->isProcessable($config)) {
            foreach ($config[Keys::SEARCH] as $entity => $entityConfig) {
                if (array_key_exists(Keys::TITLE_FIELDS, $entityConfig)) {
                    unset($config[Keys::SEARCH][$entity][Keys::TITLE_FIELDS]);
                }
            }
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/Resources/config/oro/search.yml';
    }

    private function isProcessable(array $config): bool
    {
        return array_key_exists(Keys::SEARCH, $config)
            && is_array($config[Keys::SEARCH]);
    }
}
