<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\DumpedThemes\DataGrid;

use Oro\UpgradeToolkit\YmlFixer\Rules\DumpedThemes\DataGrid\DataGridIdentityReplaceFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class DataGridIdentityReplaceFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $ruleConfiguration = [
            'alias' => 'testRequest',
            'serialized_data_key' => 'test_status',
        ];

        $this->testRule(
            DataGridIdentityReplaceFixer::class,
            self::FIXTURES_PATH . '/datagrids.yml',
            self::FIXTURES_PATH . '/ExpectedResults/identity_replace_datagrids.yml',
            $ruleConfiguration
        );
    }
}
