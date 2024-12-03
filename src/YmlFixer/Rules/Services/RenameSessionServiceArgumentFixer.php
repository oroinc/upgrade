<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Replaces usage of the session service as an argument
 * with the request_stack usage
 */
class RenameSessionServiceArgumentFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        if ($this->isProcessable($config)) {
            foreach ($config[Keys::SERVICES] as $serviceName => $serviceDef) {
                if (is_array($serviceDef) && array_key_exists(Keys::ARGUMENTS, $serviceDef)) {
                    foreach ($serviceDef[Keys::ARGUMENTS] as $key => $argument) {
                        if (is_string($argument) && str_contains($argument, '@session')) {
                            $argument = str_replace('@session', '@request_stack', $argument);
                            $config[Keys::SERVICES][$serviceName][Keys::ARGUMENTS][$key] = $argument;
                        }
                    }
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
}
