<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Rules\Workflows;

use Oro\UpgradeToolkit\YmlFixer\Rules\Workflows\WorkflowsExcludedValuesFixer;
use Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit\AbstractYmlFixerTestCase;

class WorkflowsExcludedValuesFixerTest extends AbstractYmlFixerTestCase
{
    public function test(): void
    {
        $this->testRule(
            WorkflowsExcludedValuesFixer::class,
            self::FIXTURES_PATH . '/workflows.yml',
            self::FIXTURES_PATH . '/ExpectedResults/excluded_values_workflows.yml'
        );
    }
}
