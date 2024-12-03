<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\DataGrids;

use Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids\SortersDirectionFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class SortersDirectionFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            SortersDirectionFixer::class,
            self::FIXTURES_PATH . '/datagrids.yml',
            self::FIXTURES_PATH . '/ExpectedResults/sorters_direction_datagrids.yml'
        );
    }
}
