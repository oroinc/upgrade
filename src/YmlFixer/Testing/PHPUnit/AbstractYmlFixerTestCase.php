<?php

namespace Oro\UpgradeToolkit\YmlFixer\Testing\PHPUnit;

use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;
use Oro\UpgradeToolkit\YmlFixer\Manipilator\YmlFileSourceManipulator;
use Oro\UpgradeToolkit\YmlFixer\ValueObject\YmlDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Allows to simplify test creation for newly added rules
 */
class AbstractYmlFixerTestCase extends TestCase
{
    public const FIXTURES_PATH = './vendor/oro/upgrade-toolkit/tests/Unit/YmlFixer/Fixtures';
    private string $sourceFilePath;
    private string $expectedFilePath;
    private YmlFixerInterface $rule;
    private YmlDefinition $def;
    private YmlFileSourceManipulator $manipulator;

    private function setMeUp(
        string $ruleClass,
        string $sourceFilePath,
        string $expectedResultFilePath,
        ?array $ruleConfig = null
    ): void {
        $this->sourceFilePath = realpath($sourceFilePath);
        $this->expectedFilePath = realpath($expectedResultFilePath);
        $this->rule = $ruleConfig ? new $ruleClass($ruleConfig) : new $ruleClass();
        $this->manipulator = new YmlFileSourceManipulator();
        $this->def = $this->manipulator->getYmlDefinition($this->sourceFilePath);
    }

    protected function testRule(
        string $ruleClass,
        string $sourceFilePath,
        string $expectedResultFilePath,
        ?array $ruleConfig = null
    ): void {
        $this->setMeUp($ruleClass, $sourceFilePath, $expectedResultFilePath, $ruleConfig);
        $this->runRuleTest();
    }

    private function runRuleTest(): void
    {
        $config = $this->def->getArrayDefinition();
        $this->rule->fix($config);
        $this->def->setArrayDefinition($config);
        $this->def = $this->manipulator->setUpdatedYmlContent($this->def);

        $actual = $this->def->getUpdatedStringDefinition();
        $expected = $this->manipulator->getYmlDefinition($this->expectedFilePath)->getStringDefinition();

        $this->assertSame($expected, $actual);
    }
}
