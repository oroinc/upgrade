<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Services;

use Oro\UpgradeToolkit\YmlFixer\Rules\Services\RenameServiceFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class RenameServiceFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $ruleConfiguration = ['test_service' => 'NEW_test_service'];
        $this->testRule(
            RenameServiceFixer::class,
            self::FIXTURES_PATH . '/services.yml',
            self::FIXTURES_PATH . '/ExpectedResults/rename_services.yml',
            $ruleConfiguration
        );
    }
}
