<?php

namespace Oro\UpgradeToolkit\Tests\Unit\Signature;

use Oro\UpgradeToolkit\Configuration\SignatureConfig;
use Oro\UpgradeToolkit\Rector\Signature\SignatureBuilder;
use PHPUnit\Framework\TestCase;

class SignatureBuilderTest extends TestCase
{
    private SignatureBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SignatureBuilder();
    }

    /**
     * @dataProvider buildDataProvider
     */
    public function testBuild(array $classes, array $expected)
    {
        $actual = $this->builder->build($classes);
        $this->assertSame($expected, $actual);
    }

    public function buildDataProvider(): array
    {
        return [
            [
                [],
                [
                    SignatureConfig::PROPERTY_TYPES => [],
                    SignatureConfig::METHOD_RETURN_TYPES => [],
                    SignatureConfig::METHOD_PARAM_TYPES => [],
                ],
            ],
            [
                [
                    'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass1',
                    'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass2',
                    'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass3',
                ],
                [
                    SignatureConfig::PROPERTY_TYPES => [
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass1',
                            'id',
                            'int|null',
                        ],
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass2',
                            'uuid',
                            'string',
                        ],
                    ],
                    SignatureConfig::METHOD_RETURN_TYPES => [
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass1',
                            'getId',
                            'int|null',
                        ],
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass1',
                            'setId',
                            'void',
                        ],
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass2',
                            'getUuid',
                            'string',
                        ],
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass2',
                            'getValue',
                            'int',
                        ],
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass2',
                            'setUuid',
                            'void',
                        ],
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass2',
                            'setValue',
                            'void',
                        ],
                    ],
                    SignatureConfig::METHOD_PARAM_TYPES => [
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass1',
                            'setId',
                            0,
                            'int|null',
                        ],
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass2',
                            'setUuid',
                            0,
                            'string',
                        ],
                        [
                            'Oro\UpgradeToolkit\Tests\Unit\Fixtures\TestClass2',
                            'setValue',
                            0,
                            'int',
                        ],
                    ],
                ]
            ],
        ];
    }
}
