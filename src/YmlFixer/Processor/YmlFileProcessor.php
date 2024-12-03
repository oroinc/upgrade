<?php

namespace Oro\UpgradeToolkit\YmlFixer\Processor;

use Oro\UpgradeToolkit\YmlFixer\Config\Config;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFileVisitorInterface;
use Oro\UpgradeToolkit\YmlFixer\Contract\YmlFixerInterface;
use Oro\UpgradeToolkit\YmlFixer\Manipilator\YmlFileSourceManipulator;
use Oro\UpgradeToolkit\YmlFixer\ValueObject\YmlDefinition;

/**
 * Allows one to do needed changes to the .yml files
 */
class YmlFileProcessor
{
    public function processFiles(
        array $filePaths,
        ?callable $postFileCallback = null,
    ): array {
        $processedDefinitions = [];
        foreach ($filePaths as $filePath) {
            if ($this->isProcessable($filePath)) {
                $errors = $this->checkFile($filePath)->getErrors();
                $processResult = empty($errors) ? $this->processFile($filePath) : $this->checkFile($filePath);
                if ($processResult) {
                    $processedDefinitions[] = $processResult;
                }
            }
            if (is_callable($postFileCallback)) {
                $postFileCallback(1);
            }
        }

        return $processedDefinitions;
    }

    public function processFile(string $filePath): ?YmlDefinition
    {
        $config = new Config();
        $visitors = $config->getVisitors();
        foreach ($visitors as $visitor) {
            $visitor = new $visitor();
            if ($visitor instanceof YmlFileVisitorInterface) {
                $def = $visitor->visit($filePath);
            }
        }

        return $def ?? null;
    }

    private function checkFile(mixed $filePath): YmlDefinition
    {
        return (new YmlFileSourceManipulator())->checkYmlFile($filePath);
    }

    private function isProcessable(string $filePath): bool
    {
        $pathPatterns = $this->getRulesPathPatterns();
        foreach ($pathPatterns as $pattern) {
            preg_match($this->convertGlobToRegex($pattern), $filePath, $matches);
            if (!empty($matches)) {
                return true;
            }
        }

        return false;
    }

    private function convertGlobToRegex(string $glob): string
    {
        $regex = preg_quote($glob, '#');

        // Replace '**' with '.*' to match any number of directories
        // Replace '*' with '[^/]*' to match any characters except '/'
        // Replace '?' with '.' to match any single character
        $regex = str_replace(array('\*\*', '\*', '\?'), array('.*', '[^/]*', '.'), $regex);

        // Handle ranges [abc] or [a-z], removing escaping
        $regex = preg_replace_callback(
            '/\[([^\]]+)\]/',
            function ($matches) {
                return '[' . $matches[1] . ']';
            },
            $regex
        );
        // Handle {abc,def} as (abc|def)
        $regex = preg_replace('/\{([^}]+)\}/', '($1)', $regex);
        $regex = str_replace(',', '|', $regex);

        return '#^' . $regex . '$#';
    }

    private function getRulesPathPatterns(): array
    {
        $pathPatterns = [];

        $config = new Config();
        $rules = $config->getRules();

        foreach ($rules as $rule) {
            $rule = new $rule();
            if ($rule instanceof YmlFixerInterface) {
                $pathPatterns[] = $rule->matchFile();
            }
        }

        return array_unique($pathPatterns);
    }
}
