<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\DumpedThemes\DataGrid;

use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Replaces IDENTITY statements to the JSON_EXTRACT statements by the provided config
 * E.g.
 *  old - IDENTITY(request.internal_status)
 *  new - JSON_EXTRACT(request.serialized_data, 'internal_status')
 */
class DataGridIdentityReplaceFixer implements YmlFixerInterface
{
    public function __construct(
        private ?array $ruleConfiguration = null,
    ) {
    }

    #[\Override]
    public function fix(array &$config): void
    {
        $entityAlias = $this->config()['alias'];
        $serializedDataKey = $this->config()['serialized_data_key'];

        $identityReplace = function ($value) use ($entityAlias, $serializedDataKey) {
            return preg_replace_callback(
                '/IDENTITY\((\w+)\.(\w+)\)/',
                function ($matches) use ($serializedDataKey, $entityAlias) {
                    // $matches[1] - E.g. request
                    // $matches[2] - E.g. internal_status
                    if (empty($matches[1]) || empty($matches[2])) {
                        return $matches[0];
                    }

                    if ($entityAlias !== $matches[1] && $serializedDataKey !== $matches[2]) {
                        return $matches[0];
                    }

                    return "JSON_EXTRACT({$matches[1]}.serialized_data, '{$matches[2]}')";
                },
                $value
            );
        };

        $this->replace($config, $identityReplace);
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/ThemeDefault*Bundle/Resources/views/layouts/default_**/config/datagrids.yml';
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
            'alias' => 'request',
            'serialized_data_key' => 'internal_status',
        ];
    }
}
