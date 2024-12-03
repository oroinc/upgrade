<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Replaces usage of the old enum_value_provider service
 * to the new one enum_options_provider in the services configuration
 */
class RenameEnumValueProviderServiceArgumentFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        if ($this->isProcessable($config)) {
            foreach ($config[Keys::SERVICES] as $serviceName => $serviceDef) {
                if (is_array($serviceDef)) {
                    $serviceArgumentRenameCallback = function ($value) {
                        return str_replace(
                            '@oro_entity_extend.enum_value_provider',
                            '@oro_entity_extend.enum_options_provider',
                            $value
                        );
                    };

                    $this->rename($serviceDef, $serviceArgumentRenameCallback);
                    $config[Keys::SERVICES][$serviceName] = $serviceDef;
                }
            }
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/Resources/config/services.yml';
    }

    private function isProcessable(array $config): bool
    {
        return array_key_exists(Keys::SERVICES, $config) && is_array($config[Keys::SERVICES]);
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
