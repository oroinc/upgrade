<?php

namespace Oro\UpgradeToolkit\Tests\Unit\YmlFixer\Manipulator;

use Oro\UpgradeToolkit\YmlFixer\Manipilator\YamlSourceManipulator;
use PHPUnit\Framework\TestCase;

class YamlSourceManipulatorTest extends TestCase
{
    private const FIXTURES_PATH = './vendor/oro/upgrade-toolkit/tests/Unit/YmlFixer/Fixtures';

    private string $fileContent;

    protected function setUp(): void
    {
        $filePath = realpath(self::FIXTURES_PATH . '/test.yml');
        $this->fileContent = file_get_contents($filePath);

        parent::setUp();
    }

    public function testConstructException(): void
    {
        $this->expectException("InvalidArgumentException");
        $this->expectExceptionMessage("Only YAML with a top-level array structure is supported");

        $contents = "\n\n";
        $manipulator = new YamlSourceManipulator($contents);
    }

    public function testGetContents(): void
    {
        $manipulator = new YamlSourceManipulator($this->fileContent);
        $actualContents = $manipulator->getContents();

        $this->assertSame($this->fileContent, $actualContents);
    }

    public function testGetData(): void
    {
        $expectedData = ['source' => ['text' => 'This file should be detected while testing']];

        $manipulator = new YamlSourceManipulator($this->fileContent);
        $actualData = $manipulator->getData();

        $this->assertSame($expectedData, $actualData);
    }

    public function testSetData(): void
    {
        $updatedData = [
            'source' => [
                'text' => 'This file should be detected while testing',
                'author' => 'Username',
            ],
        ];

        $expectedData = "source:\n    text: 'This file should be detected while testing'\n    author: Username\n";

        $manipulator = new YamlSourceManipulator($this->fileContent);
        $manipulator->setData($updatedData);

        $this->assertSame($expectedData, $manipulator->getContents());
    }

    public function testCreateCommentLine(): void
    {
        $manipulator = new YamlSourceManipulator($this->fileContent);

        $expetedCommentLine = $manipulator::COMMENT_PLACEHOLDER_VALUE . "This is a test comment";
        $commentLine = $manipulator->createCommentLine("This is a test comment");

        $this->assertSame($expetedCommentLine, $commentLine);

        $data = $manipulator->getData();
        $data['source'][] = $commentLine;

        $manipulator->setData($data);

        $expectedContents = $this->fileContent . "    #This is a test comment\n";
        $this->assertSame($expectedContents, $manipulator->getContents());
    }

    public function testCreateEmptyLine(): void
    {
        $manipulator = new YamlSourceManipulator($this->fileContent);

        $expetedEmptyLine = $manipulator::EMPTY_LINE_PLACEHOLDER_VALUE;
        $emptyLine = $manipulator->createEmptyLine();

        $this->assertSame($expetedEmptyLine, $emptyLine);

        $data = $manipulator->getData();
        $data['source'][] = $emptyLine;

        $manipulator->setData($data);

        $expectedContents = $this->fileContent . "\n";
        $this->assertSame($expectedContents, $manipulator->getContents());
    }

    /**
     * @dataProvider ymlDataProvider
     */
    public function testEdgeCases(string $unprocessedFile, string $processedFile, array $processedData): void
    {
        $contents = file_get_contents($unprocessedFile);
        $manipulator = new YamlSourceManipulator($contents);
        $manipulator->setData($processedData);

        $expectedContents = file_get_contents($processedFile);
        $this->assertSame($expectedContents, $manipulator->getContents());
    }

    public function ymlDataProvider(): array
    {
        return [
            [
                realpath(self::FIXTURES_PATH . '/test.yml'),
                realpath(self::FIXTURES_PATH . '/test.yml'),
                ['source' => ['text' => 'This file should be detected while testing']],
            ],
            [
                realpath(self::FIXTURES_PATH . '/YamlSourceManipulator/case1.yml'),
                realpath(self::FIXTURES_PATH . '/YamlSourceManipulator/case1_res.yml'),
                [
                    'root' => [
                        'key1' => [
                            0 => 'NEWvalue1',
                            1 => 'value2',
                            2 => 'value3',
                        ],
                        'key2' => [
                            0 => 'value1',
                            1 => 'NEWvalue2',
                            2 => 'NEWvalue3',
                        ],
                        'key3' => [
                            0 => 'value1',
                            1 => 'value2',
                            2 => 'NEWvalue3',
                        ],
                    ],
                ],
            ],
            [
                realpath(self::FIXTURES_PATH . '/YamlSourceManipulator/case2.yml'),
                realpath(self::FIXTURES_PATH . '/YamlSourceManipulator/case2_res.yml'),
                [
                    'root' => [
                        'key1' => [
                            0 => [
                                0 => 'value1',
                                1 => [
                                    0 => 'NEWvalue2'
                                ],
                            ],
                        ]
                    ],
                ],
            ],
            [
                realpath(self::FIXTURES_PATH . '/YamlSourceManipulator/case3.yml'),
                realpath(self::FIXTURES_PATH . '/YamlSourceManipulator/case3_res.yml'),
                [
                    'root' => [
                        'key1' => [
                            0 => '!php/const Oro\Test\Class::TEST_CONST'
                        ],
                        'key2' => [
                            '!php/const Oro\Test\Class1::TEST_CONST' => 'NEWvalue'
                        ],
                    ],
                ],
            ],
            [
                realpath(self::FIXTURES_PATH . '/YamlSourceManipulator/case4.yml'),
                realpath(self::FIXTURES_PATH . '/YamlSourceManipulator/case4_res.yml'),
                [
                    'root' => [
                        'key1' => [
                            200 => 'Ok',
                            500 => 'Server Error',
                        ],
                        'key2' => [
                            'status_200' => 'Success',
                            'Status404' => 'Not Found',
                        ],
                    ],
                ],
            ],
            [
                realpath(self::FIXTURES_PATH . '/YamlSourceManipulator/case5.yml'),
                realpath(self::FIXTURES_PATH . '/YamlSourceManipulator/case5_res.yml'),
                [
                    'root' => [
                        'key1' => "NEW \n multi-line \n value",
                        'key2' => 'One-line value'
                    ],
                ],
            ],
        ];
    }
}
