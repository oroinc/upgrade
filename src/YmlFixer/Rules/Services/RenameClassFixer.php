<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Renames classes in the service configs
 */
class RenameClassFixer implements YmlFixerInterface
{
    public function __construct(
        private ?array $ruleConfiguration = null,
    ) {
    }

    #[\Override]
    public function fix(array &$config): void
    {
        if (!$this->isProcessable($config)) {
            return;
        }

        $services = $config[Keys::SERVICES];
        $configuration = $this->config();
        $serviceRenameCallback = function ($value) use ($configuration) {
            foreach ($configuration as $old => $new) {
                $value = $old === $value ? $new : $value;
            }

            return $value;
        };

        $this->rename($services, $serviceRenameCallback);
        $config[Keys::SERVICES] = $services;
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/Resources/config/**.yml';
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

    private function config(): array
    {
        // old\class => new\class
        return $this->ruleConfiguration ?? [
            'Symfony\\Component\\Validator\\Constraints\\ExpressionLanguageSyntax'
            => 'Symfony\\Component\\Validator\\Constraints\\ExpressionSyntax',
            'Symfony\\Component\\Validator\\Constraints\\ExpressionLanguageSyntaxValidator'
            => 'Symfony\\Component\\Validator\\Constraints\\ExpressionSyntaxValidator',
        ];
    }
}
