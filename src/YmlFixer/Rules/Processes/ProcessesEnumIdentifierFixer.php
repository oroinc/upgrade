<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Processes;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Updates Identifier value in the processes configuration
 * according to the new implementation of the enums
 */
class ProcessesEnumIdentifierFixer implements YmlFixerInterface
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
                    $this->replaceIdentifiersValues($actionsConfig);

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

    private function replaceIdentifiersValues(array &$array): void
    {
        foreach ($array as &$value) {
            if (!is_array($value)) {
                continue;
            }

            if (array_key_exists(Keys::ENUM_CODE, $value) && array_key_exists(Keys::IDENTIFIER, $value)) {
                $this->replaceEnumCodeIdentifier($value);
                continue;
            }

            if (array_key_exists(Keys::CLASS_KEY, $value) && array_key_exists(Keys::IDENTIFIER, $value)) {
                $this->replaceClassIdentifier($value);
                continue;
            }

            $this->replaceIdentifiersValues($value); // Recurse
        }
    }

    private function replaceEnumCodeIdentifier(array &$value): void
    {
        $newIdentifier = $value[Keys::ENUM_CODE] . '.' . $value[Keys::IDENTIFIER];

        if ($this->isReplacementNeeded($value[Keys::IDENTIFIER], $newIdentifier)) {
            $value[Keys::IDENTIFIER] = $newIdentifier;
        }
    }

    private function replaceClassIdentifier(array &$value): void
    {
        $class = $value[Keys::CLASS_KEY];
        if (!str_contains($class, 'Extend\Entity\EV_')) {
            return;
        }

        $enumCode = str_replace('Extend\Entity\EV_', '', $class);
        $newIdentifier = strtolower($enumCode) . '.' . $value[Keys::IDENTIFIER];

        if ($this->isReplacementNeeded($value[Keys::IDENTIFIER], $newIdentifier)) {
            $value[Keys::IDENTIFIER] = $newIdentifier;
        }
    }

    private function isReplacementNeeded(string $old, string $new): bool
    {
        return $old !== $new && !str_contains($old, '.') && !str_contains($old, '$');
    }
}
