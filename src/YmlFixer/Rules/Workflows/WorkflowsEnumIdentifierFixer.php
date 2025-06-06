<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Workflows;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Updates Identifier value in the workflows configuration
 * according to the new implementation of the enums
 */
class WorkflowsEnumIdentifierFixer implements YmlFixerInterface
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

    private function traverse(array &$node): void
    {
        foreach ($node as $key => &$value) {
            if (is_array($value)) {
                $this->traverse($value);
            }

            if (Keys::ENUM_CODE === $key) {
                if (array_key_exists(Keys::IDENTIFIER, $node)) {
                    $newIdentifier = $value . "." . $node[Keys::IDENTIFIER];

                    if ($this->isReplacementNeeded($node[Keys::IDENTIFIER], $newIdentifier)) {
                        $node[Keys::IDENTIFIER] = $newIdentifier;
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
