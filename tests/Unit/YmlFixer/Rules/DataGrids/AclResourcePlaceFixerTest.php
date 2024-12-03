<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\DataGrids;

use Oro\UpgradeToolkit\YmlFixer\Rules\DataGrids\AclResourcePlaceFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class AclResourcePlaceFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            AclResourcePlaceFixer::class,
            self::FIXTURES_PATH . '/datagrids.yml',
            self::FIXTURES_PATH . '/ExpectedResults/acl_resource_place_datagrids.yml'
        );
    }
}
