<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\DumpedThemes\DataGrid;

use Oro\UpgradeToolkit\YmlFixer\Rules\DumpedThemes\DataGrid\DataGridQueryFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class DataGridQueryFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $ruleConfiguration = [
            'test-user-grid' => [
                'entity_alias' => 'testUser',
                'serialized_data_key' => 'test_status',
                'enum_code' => 'tu_test_status',
            ],
            'test-products-grid' => [
                'entity_alias' => 'testProduct',
                'serialized_data_key' => 'p_test_status',
                'enum_code' => 'pr_test_status',
            ],
        ];

        $this->testRule(
            DataGridQueryFixer::class,
            self::FIXTURES_PATH . '/datagrids.yml',
            self::FIXTURES_PATH . '/ExpectedResults/query_fixer_datagrids.yml',
            $ruleConfiguration
        );
    }
}
