<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Workflows;

use Oro\UpgradeToolkit\YmlFixer\Rules\Workflows\WorkflowsEnumIdentifierFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class WorkflowsEnumIdentifierFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            WorkflowsEnumIdentifierFixer::class,
            self::FIXTURES_PATH . '/workflows.yml',
            self::FIXTURES_PATH . '/ExpectedResults/enum_indetifier_workflows.yml'
        );
    }
}
