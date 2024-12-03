<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Process;

use Oro\UpgradeToolkit\YmlFixer\Rules\Processes\ProcessesExtendEntityReplaceFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class ProcessesExtendEntityReplaceFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            ProcessesExtendEntityReplaceFixer::class,
            self::FIXTURES_PATH . '/processes.yml',
            self::FIXTURES_PATH . '/ExpectedResults/extend_entity_replace_processes.yml'
        );
    }
}
