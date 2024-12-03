<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\DataGrids;

use Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids\SkipAclCheckOptionFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class SkipAclCheckOptionFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            SkipAclCheckOptionFixer::class,
            self::FIXTURES_PATH . '/datagrids.yml',
            self::FIXTURES_PATH . '/ExpectedResults/skip_acl_check_option_datagrids.yml'
        );
    }
}
