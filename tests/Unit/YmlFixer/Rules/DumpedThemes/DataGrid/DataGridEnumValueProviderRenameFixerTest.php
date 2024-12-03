<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\DumpedThemes\DataGrid;

use Oro\UpgradeToolkit\YmlFixer\Rules\DumpedThemes\DataGrid\DataGridEnumValueProviderRenameFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class DataGridEnumValueProviderRenameFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            DataGridEnumValueProviderRenameFixer::class,
            self::FIXTURES_PATH . '/datagrids.yml',
            self::FIXTURES_PATH . '/ExpectedResults/rename_provider_datagrids.yml'
        );
    }
}
