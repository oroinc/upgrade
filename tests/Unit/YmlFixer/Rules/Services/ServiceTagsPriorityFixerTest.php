<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Rules\Services\ServiceTagsPriorityFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class ServiceTagsPriorityFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $ruleConfiguration = ['test_tag'];
        $this->testRule(
            ServiceTagsPriorityFixer::class,
            self::FIXTURES_PATH . '/services.yml',
            self::FIXTURES_PATH . '/ExpectedResults/tags_priority_services.yml',
            $ruleConfiguration
        );
    }
}
