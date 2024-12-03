<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\DataGrids;

use Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids\EnabledConfigKeyFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class EnabledConfigKeyFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            EnabledConfigKeyFixer::class,
            self::FIXTURES_PATH . '/datagrids.yml',
            self::FIXTURES_PATH . '/ExpectedResults/enabled_config_key_datagrids.yml'
        );
    }
}
