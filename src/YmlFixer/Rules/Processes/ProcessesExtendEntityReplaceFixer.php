<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Processes;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Replaces "EV_" entity definitions with EnumOptions definitions
 */
class ProcessesExtendEntityReplaceFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        if ($this->isProcessable($config)) {
            foreach ($config[Keys::PROCESSES][Keys::DEFINITIONS] as $definitionKey => $definition) {
                if (
                    is_array($definition)
                    && array_key_exists(Keys::ACTIONS_CONFIG, $definition)
                    && is_array($definition[Keys::ACTIONS_CONFIG])
                ) {
                    $actionsConfig = $definition[Keys::ACTIONS_CONFIG];

                    $EntityRenameCallback = function ($value) {
                        $needle = 'Extend\Entity\EV_';
                        if (str_contains($value, $needle)) {
                            return str_replace(
                                $value,
                                'Oro\Bundle\EntityExtendBundle\Entity\EnumOption',
                                $value
                            );
                        }

                        return $value;
                    };

                    $this->replace($actionsConfig, $EntityRenameCallback);

                    $config[Keys::PROCESSES][Keys::DEFINITIONS][$definitionKey][Keys::ACTIONS_CONFIG] = $actionsConfig;
                }
            }
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/Resources/config/oro/processes.yml';
    }

    private function isProcessable(array $config): bool
    {
        return array_key_exists(Keys::PROCESSES, $config)
            && is_array($config[Keys::PROCESSES])
            && array_key_exists(Keys::DEFINITIONS, $config[Keys::PROCESSES])
            && is_array($config[Keys::PROCESSES][Keys::DEFINITIONS]);
    }

    private function replace(array &$array, callable $callback): void
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->replace($value, $callback);
            } elseif (is_string($value)) {
                $value = $callback($value);
            }
        }
    }
}
