<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Manipulator;

use Oro\UpgradeToolkit\YmlFixer\Manipilator\YmlFileSourceManipulator;
use Oro\UpgradeToolkit\YmlFixer\ValueObject\YmlDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class YmlFileSourceManipulatorTest extends TestCase
{
    private const FIXTURES_PATH = './vendor/oro/upgrade-toolkit/tests/Unit/YmlFixer/Fixtures';

    private YmlFileSourceManipulator $manipulator;

    protected function setUp(): void
    {
        $this->manipulator = new YmlFileSourceManipulator();
    }

    /**
     * @dataProvider ymlDefinitionDataProvider
     */
    public function testCheckYmlFile(string $filePath, YmlDefinition $expected): void
    {
        $actual = $this->manipulator->checkYmlFile($filePath);

        $this->compare($expected, $actual);
    }

    private function compare(YmlDefinition $expected, YmlDefinition $actual): void
    {
        $this->assertSame(count($expected->getErrors()), count($actual->getErrors()));
        $this->assertSame($expected->getFilePath(), $actual->getFilePath());
        $this->assertSame($expected->getStringDefinition(), $actual->getStringDefinition());
        $this->assertSame($expected->getArrayDefinition(), $actual->getArrayDefinition());
        $this->assertSame($expected->getUpdatedStringDefinition(), $actual->getUpdatedStringDefinition());
        $this->assertSame($expected->getAppliedRules(), $actual->getAppliedRules());

        /** @var \Throwable $error */
        foreach ($expected->getErrors() as $index => $error) {
            $this->assertSame($error->getMessage(), $actual->getErrors()[$index]->getMessage());
        }
    }

    public function ymlDefinitionDataProvider(): array
    {
        $regularFilePath = realpath(self::FIXTURES_PATH . '/test.yml');
        $regularFileYmlDefinition = new YmlDefinition(
            filePath: $regularFilePath,
            stringDefinition: file_get_contents($regularFilePath),
            arrayDefinition: ['source' => ['text' => 'This file should be detected while testing']],
        );

        $emptyFilePath = realpath(self::FIXTURES_PATH . '/empty.yml');
        $emptyFileYmlDefinition = new YmlDefinition(filePath: $emptyFilePath);
        $emptyFileYmlDefinition->setError(new \Exception(sprintf('File is empty: %s', $emptyFilePath)));

        $invalidSyntaxFilePath = realpath(self::FIXTURES_PATH . '/invalid_syntax.yaml');
        $invalidSyntaxYmlDefinition = new YmlDefinition(filePath: $invalidSyntaxFilePath);
        $invalidSyntaxYmlDefinition->setError(
            new \Exception(sprintf('Unable to parse at line 1 (near "    source:").'))
        );

        $processedFilePath = realpath(self::FIXTURES_PATH . '/ExpectedResults/query_fixer_datagrids.yml');
        $ymlContent = file_get_contents($processedFilePath);
        $ymlContent = preg_replace('/!!binary\s+[^\n]*/', "'$0'", $ymlContent);
        $processedYmlDefinition = new YmlDefinition(
            filePath: $processedFilePath,
            stringDefinition: file_get_contents($processedFilePath),
            arrayDefinition: Yaml::parse($ymlContent),
        );

        return [
            [
                $regularFilePath,
                $regularFileYmlDefinition,
            ],
            [
                $emptyFilePath,
                $emptyFileYmlDefinition,
            ],
            [
                $invalidSyntaxFilePath,
                $invalidSyntaxYmlDefinition,
            ],
            [
                $processedFilePath,
                $processedYmlDefinition,
            ],
        ];
    }
}
