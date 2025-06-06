<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Workflows;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Updates excluded_values values in the workflows configuration
 * according to the new implementation of the enums
 */
class WorkflowsExcludedValuesFixer implements YmlFixerInterface
{
    #[\Override]
    public function fix(array &$config): void
    {
        if ($this->isProcessable($config)) {
            foreach ($config[Keys::WORKFLOWS] as &$workflow) {
                $this->traverse($workflow);
            }
            unset($workflow);
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/Resources/config/oro/workflows/**.yml';
    }

    private function isProcessable(array $config): bool
    {
        return array_key_exists(Keys::WORKFLOWS, $config);
    }

    private function traverse(?array &$node): void
    {
        if (is_array($node)) {
            foreach ($node as $key => &$value) {
                if (is_array($value)) {
                    $this->traverse($value);
                }

                if (Keys::ENUM_CODE === $key) {
                    if (array_key_exists(Keys::EXCLUDED_VALUES, $node)) {
                        foreach ($node[Keys::EXCLUDED_VALUES] as $index => $excludedValue) {
                            $newExcludedValue = $value . "." . $excludedValue;

                            if ($this->isReplacementNeeded($excludedValue, $newExcludedValue)) {
                                $node[Keys::EXCLUDED_VALUES][$index] = $newExcludedValue;
                            }
                        }
                    }
                }
            }
        }
    }

    private function isReplacementNeeded(string $old, string $new): bool
    {
        return $old !== $new && !str_contains($old, '.') && !str_contains($old, '$');
    }
}
