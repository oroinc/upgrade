<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Processes;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Replaces IDENTITY statements to the JSON_EXTRACT statements by the provided config
 */
class ProcessesIdentityReplaceFixer implements YmlFixerInterface
{
    public function __construct(
        private ?array $ruleConfiguration = null,
    ) {
    }

    #[\Override]
    public function fix(array &$config): void
    {
        if ($this->isProcessable($config)) {
            foreach ($config[Keys::PROCESSES][Keys::DEFINITIONS] as $definitionName => $definition) {
                if (is_array($definition)) {
                    foreach ($this->config() as $ruleConfiguration) {
                        $serializedDataKey = $ruleConfiguration['serialized_data_key'];

                        $identityReplaceCallback = function ($value) use ($serializedDataKey) {
                            return preg_replace_callback(
                                '/IDENTITY\((\w+)\.(\w+)\)/',
                                function ($matches) use ($serializedDataKey) {
                                    // $matches[1] - E.g. e
                                    // $matches[2] - E.g. internal_status
                                    if (empty($matches[1]) || empty($matches[2])) {
                                        return $matches[0];
                                    }

                                    if ($serializedDataKey !== $matches[2]) {
                                        return $matches[0];
                                    }

                                    return "JSON_EXTRACT({$matches[1]}.serialized_data, '{$matches[2]}')";
                                },
                                $value
                            );
                        };

                        $this->replace($definition, $identityReplaceCallback);
                        $config[Keys::PROCESSES][Keys::DEFINITIONS][$definitionName] = $definition;
                    }
                }
            }
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/Resources/config/oro/processes.yml';
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

    private function config(): array
    {
        return $this->ruleConfiguration ?? [
            [
                'serialized_data_key' => 'internal_status',
            ],
        ];
    }

    private function isProcessable(array $config): bool
    {
        return array_key_exists(Keys::PROCESSES, $config)
            && is_array($config[Keys::PROCESSES])
            && array_key_exists(Keys::DEFINITIONS, $config[Keys::PROCESSES])
            && is_array($config[Keys::PROCESSES][Keys::DEFINITIONS]);
    }
}
