<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Process;

use Oro\UpgradeToolkit\YmlFixer\Rules\Processes\ProcessesEnumIdentifierFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class ProcessesEnumIdentifierFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            ProcessesEnumIdentifierFixer::class,
            self::FIXTURES_PATH . '/processes.yml',
            self::FIXTURES_PATH . '/ExpectedResults/enum_indetifier_processes.yml'
        );
    }
}
