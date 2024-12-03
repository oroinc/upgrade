<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Process;

use Oro\UpgradeToolkit\YmlFixer\Rules\Processes\ProcessesIdentityReplaceFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class ProcessesIdentityReplaceFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $ruleConfiguration = [
            [
                'serialized_data_key' => 'test_status',
            ],
        ];

        $this->testRule(
            ProcessesIdentityReplaceFixer::class,
            self::FIXTURES_PATH . '/processes.yml',
            self::FIXTURES_PATH . '/ExpectedResults/identity_replace_processes.yml',
            $ruleConfiguration
        );
    }
}
