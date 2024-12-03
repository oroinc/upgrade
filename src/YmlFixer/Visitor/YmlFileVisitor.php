<?php

namespace Oro\UpgradeToolkit\YmlFixer\Visitor;

use Oro\UpgradeToolkit\YmlFixer\Config\Config;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFileVisitorInterface;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;
use Oro\UpgradeToolkit\YmlFixer\Manipilator\YmlFileSourceManipulator;
use Oro\UpgradeToolkit\YmlFixer\ValueObject\YmlDefinition;

/**
 * Allows one to apply rules to each of the processed .yml files
 */
class YmlFileVisitor implements YmlFileVisitorInterface
{
    private Config $config;

    public function __construct()
    {
        $this->config = new Config();
    }

    public function visit(string $filePath): YmlDefinition
    {
        $sourceManipulator = new YmlFileSourceManipulator();
        $def = $sourceManipulator->getYmlDefinition($filePath);

        return (null === $def->getArrayDefinition()) ? $def : $this->applyRules($def);
    }

    private function applyRules(YmlDefinition $ymlDefinition): YmlDefinition
    {
        $rules = $this->config->getRules();
        foreach ($rules as $rule) {
            $rule = new $rule();
            if ($rule instanceof YmlFixerInterface) {
                if ($this->isApplicable($ymlDefinition->getFilePath(), $rule->matchFile())) {
                    $def = $ymlDefinition->getArrayDefinition();
                    $rule->fix($def);
                    $ymlDefinition->setArrayDefinition($def);
                    $ymlDefinition->setAppliedRule($rule::class);
                    $ymlDefinition->updated();
                }
            }
        }

        return $ymlDefinition;
    }

    private function isApplicable(string $filePath, string $pattern): bool
    {
        $pattern = str_replace('**', '*', $pattern);

        return fnmatch($pattern, $filePath);
    }
}
