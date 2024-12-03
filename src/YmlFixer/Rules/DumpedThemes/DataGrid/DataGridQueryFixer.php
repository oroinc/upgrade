<?php

namespace Oro\UpgradeToolkit\YmlFixer\Rules\DumpedThemes\DataGrid;

use Oro\UpgradeToolkit\YmlFixer\Config\YmlConfigKeys as Keys;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;

/**
 * Updates query, columns, filters, and sorters datagrid blocks by the provided config
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DataGridQueryFixer implements YmlFixerInterface
{
    public function __construct(
        private ?array $ruleConfiguration = null,
    ) {
    }

    private ?string $joinAlias = null;
    private ?array $columnKeys = null;

    #[\Override]
    public function fix(array &$config): void
    {
        foreach (array_keys($config[Keys::DATAGRIDS]) as $datagrid) {
            if (array_key_exists($datagrid, $this->config())) {
                // Get "JoinAlias" and Delete Join
                $this->getAliasFromJoins($config, $datagrid);
                // Update Selects
                $this->updateSelect($config, $datagrid);
                // Update Columns, Filters, Sorters
                $this->updateColumns($config, $datagrid);
                $this->updateFilters($config, $datagrid);
                $this->updateSorters($config, $datagrid);
            }
        }
    }

    #[\Override]
    public function matchFile(): string
    {
        return '**/ThemeDefault*Bundle/Resources/views/layouts/default_**/config/datagrids.yml';
    }

    private function config(): array
    {
        return $this->ruleConfiguration ?? [
            'frontend-customer-customer-user-grid' => [
                'entity_alias' => 'customerUser',
                'serialized_data_key' => 'auth_status',
                'enum_code' => 'cu_auth_status',
            ],
            'frontend-requests-grid' => [
                'entity_alias' => 'request',
                'serialized_data_key' => 'customer_status',
                'enum_code' => 'rfp_customer_status',
            ],
        ];
    }

    private function getAliasFromJoins(array &$config, string $datagrid): void
    {
        if ($this->areJoinsProcessable($config, $datagrid)) {
            // E.g. 'customerUser', 'auth_status'
            $alias = null;
            $entityAlias = $this->config()[$datagrid]['entity_alias'];
            $serializedDataKey = $this->config()[$datagrid]['serialized_data_key'];
            $joinField = $entityAlias . "." . $serializedDataKey;

            $joins = $config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::JOIN];
            foreach ($joins as $key => $subJoins) {
                foreach ($subJoins as $subJoinKey => $subJoin) {
                    if (array_key_exists(Keys::ALIAS, $subJoin)
                        && $subJoin[Keys::JOIN] === $joinField
                    ) {
                        $alias = $subJoin[Keys::ALIAS];
                        // Unset this subJoin
                        $this->unsetJoin($config, $datagrid, $key, $subJoinKey);
                    }
                }
            }

            $this->joinAlias = $alias;
        }
    }

    private function unsetJoin(array &$config, string $datagrid, int|string $key, int|string $subJoinKey): void
    {
        unset($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::JOIN][$key][$subJoinKey]);
        if (empty($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::JOIN][$key])) {
            unset($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::JOIN][$key]);
        }
        if (empty($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::JOIN])) {
            unset($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::JOIN]);
        }
    }

    private function updateSelect(array &$config, string $datagrid): void
    {
        if ($this->isSelectProcessable($config, $datagrid)) {
            $columnKeys = null;
            $entityAlias = $this->config()[$datagrid]['entity_alias'];

            if ($this->joinAlias) {
                $pattern = "/^" . preg_quote($this->joinAlias, '/') . "\..* as /";
            } else {
                $pattern = "/^" . preg_quote($entityAlias, '/') . "\..* as /";
            }

            $serializedDataKey = $this->config()[$datagrid]['serialized_data_key'];

            $selects = $config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::SELECT];
            // Get selects like au.name as authStatus
            $filteredSelects = preg_grep($pattern, $selects);
            foreach ($filteredSelects as $key => $filteredSelect) {
                // Get columnKey. E.G. authStatus
                if (preg_match('/as\s+(.*)$/', $filteredSelect, $matches)) {
                    // OldColumnTitle => new_column_title
                    $columnKeys[$matches[1]] = $serializedDataKey;

                    $newVal = "JSON_EXTRACT($entityAlias.serialized_data, '$serializedDataKey') as $serializedDataKey";
                    $config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::SELECT][$key] = $newVal;
                }
            }

            $selects = $config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::SELECT];
            $config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::SELECT] = array_unique($selects);

            // Update Columns Keys List
            $this->columnKeys = $columnKeys;
        }
    }

    private function updateColumns(array &$config, string $datagrid): void
    {
        if ($this->areColumnsProcessable($config, $datagrid)) {
            $columnsToUpdate = null;
            $enumCode = $this->config()[$datagrid]['enum_code'];

            $columns = array_keys($config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS]);
            foreach ($this->columnKeys as $columnKeyOld => $columnKeyNew) {
                if (in_array($columnKeyOld, $columns)) {
                    $config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS][$columnKeyOld][Keys::FRONTEND_TYPE] = 'select';
                    $choices = "@oro_entity_extend.enum_options_provider->getEnumChoicesByCode('$enumCode')";
                    $config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS][$columnKeyOld][Keys::CHOICES] = $choices;

                    // @codingStandardsIgnoreStart
                    if (array_key_exists(Keys::DATA_NAME, $config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS][$columnKeyOld])) {
                        $config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS][$columnKeyOld][Keys::DATA_NAME] = $columnKeyNew;
                    }

                    if (array_key_exists(Keys::TRANSLATABLE_OPTIONS, $config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS][$columnKeyOld])) {
                        $config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS][$columnKeyOld][Keys::TRANSLATABLE_OPTIONS] = 'false';
                    }

                    $columnsToUpdate[$columnKeyOld] = $config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS][$columnKeyOld];
                }
            }

            if ($columnsToUpdate) {
                $columnsToUpdateKeys = array_keys($columnsToUpdate);
                $newColumns = [];
                foreach ($columns as $column) {
                    if (in_array($column, $columnsToUpdateKeys)) {
                        $newColumns[$this->columnKeys[$column]] = $columnsToUpdate[$column];
                    } else {
                        $newColumns[$column] = $config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS][$column];
                    }
                }
                $config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS] = $newColumns;
            }
        }
    }

    private function updateFilters(array &$config, string $datagrid): void
    {
        if ($this->areFiltersProcessable($config, $datagrid)) {
            $columnsToUpdate = null;
            $enumCode = $this->config()[$datagrid]['enum_code'];
            $columns = array_keys($config[Keys::DATAGRIDS][$datagrid][Keys::FILTERS][Keys::COLUMNS]);
            foreach ($this->columnKeys as $columnKeyOld => $columnKeyNew) {
                if (in_array($columnKeyOld, $columns)) {
                    if ('enum' === $config[Keys::DATAGRIDS][$datagrid][Keys::FILTERS][Keys::COLUMNS][$columnKeyOld][Keys::TYPE]) {
                        $config[Keys::DATAGRIDS][$datagrid][Keys::FILTERS][Keys::COLUMNS][$columnKeyOld][Keys::DATA_NAME] = $columnKeyNew;
                        $config[Keys::DATAGRIDS][$datagrid][Keys::FILTERS][Keys::COLUMNS][$columnKeyOld][Keys::ENUM_CODE] = $enumCode;
                    }

                    $columnsToUpdate[$columnKeyOld] = $config[Keys::DATAGRIDS][$datagrid][Keys::FILTERS][Keys::COLUMNS][$columnKeyOld];
                }
            }

            if ($columnsToUpdate) {
                $columnsToUpdateKeys = array_keys($columnsToUpdate);
                $newColumns = [];
                foreach ($columns as $column) {
                    if (in_array($column, $columnsToUpdateKeys)) {
                        $newColumns[$this->columnKeys[$column]] = $columnsToUpdate[$column];
                    } else {
                        $newColumns[$column] = $config[Keys::DATAGRIDS][$datagrid][Keys::FILTERS][Keys::COLUMNS][$column];
                    }
                }
                $config[Keys::DATAGRIDS][$datagrid][Keys::FILTERS][Keys::COLUMNS] = $newColumns;
            }
        }
    }

    private function updateSorters(array &$config, string $datagrid): void
    {
        if ($this->areSortersProcessable($config, $datagrid)) {
            $columnsToUpdate = null;
            $columns = array_keys($config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS][Keys::COLUMNS]);
            foreach ($this->columnKeys as $columnKeyOld => $columnKeyNew) {
                if (in_array($columnKeyOld, $columns)) {
                    $config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS][Keys::COLUMNS][$columnKeyOld][Keys::DATA_NAME] = $columnKeyNew;

                    $columnsToUpdate[$columnKeyOld] = $config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS][Keys::COLUMNS][$columnKeyOld];
                }
            }

            if ($columnsToUpdate) {
                $columnsToUpdateKeys = array_keys($columnsToUpdate);
                $newColumns = [];
                foreach ($columns as $column) {
                    if (in_array($column, $columnsToUpdateKeys)) {
                        $newColumns[$this->columnKeys[$column]] = $columnsToUpdate[$column];
                    } else {
                        $newColumns[$column] = $config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS][Keys::COLUMNS][$column];
                    }
                }
                $config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS][Keys::COLUMNS] = $newColumns;
            }
        }
        // @codingStandardsIgnoreEnd
    }

    private function isSelectProcessable(array $config, string $datagrid): bool
    {
        return array_key_exists(Keys::SOURCE, $config[Keys::DATAGRIDS][$datagrid])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE])
            && array_key_exists(Keys::QUERY, $config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY])
            && array_key_exists(Keys::SELECT, $config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::SELECT]);
    }

    private function areJoinsProcessable(array $config, string $datagrid): bool
    {
        return array_key_exists(Keys::SOURCE, $config[Keys::DATAGRIDS][$datagrid])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE])
            && array_key_exists(Keys::QUERY, $config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY])
            && array_key_exists(Keys::JOIN, $config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::SOURCE][Keys::QUERY][Keys::JOIN]);
    }

    private function areColumnsProcessable(array $config, string $datagrid): bool
    {
        return $this->columnKeys
            && array_key_exists(Keys::COLUMNS, $config[Keys::DATAGRIDS][$datagrid])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::COLUMNS]);
    }

    private function areFiltersProcessable(array $config, string $datagrid): bool
    {
        return $this->columnKeys
            && array_key_exists(Keys::FILTERS, $config[Keys::DATAGRIDS][$datagrid])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::FILTERS])
            && array_key_exists(Keys::COLUMNS, $config[Keys::DATAGRIDS][$datagrid][Keys::FILTERS])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::FILTERS][Keys::COLUMNS]);
    }

    private function areSortersProcessable(array $config, string $datagrid): bool
    {
        return $this->columnKeys
            && array_key_exists(Keys::SORTERS, $config[Keys::DATAGRIDS][$datagrid])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS])
            && array_key_exists(Keys::COLUMNS, $config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS])
            && is_array($config[Keys::DATAGRIDS][$datagrid][Keys::SORTERS][Keys::COLUMNS]);
    }
}
