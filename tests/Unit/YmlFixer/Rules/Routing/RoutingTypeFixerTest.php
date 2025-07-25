<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Routing;

use Oro\UpgradeToolkit\YmlFixer\Rules\Routing\RoutingTypeFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class RoutingTypeFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            RoutingTypeFixer::class,
            self::FIXTURES_PATH . '/routing.yml',
            self::FIXTURES_PATH . '/ExpectedResults/attribute_routing.yml'
        );
    }
}
