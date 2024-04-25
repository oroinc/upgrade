<?php

namespace Oro\Tests\Unit\Signature;

use Oro\Rector\Signature\SignatureBuilder;
use Oro\Rector\Signature\SignatureConfig;
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

    public function testBuildException()
    {
        $namespace = 'Name\Space\Where\Class\Does\Not\Exist';

        $this->expectException('ReflectionException');
        $this->expectExceptionMessage(
            sprintf('Class "%s" does not exist', $namespace)
        );

        $this->builder->build([$namespace]);
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
                    'Oro\Tests\Unit\Fixtures\TestClass1',
                    'Oro\Tests\Unit\Fixtures\TestClass2',
                    'Oro\Tests\Unit\Fixtures\TestClass3',
                ],
                [
                    SignatureConfig::PROPERTY_TYPES => [
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass1',
                            'id',
                            'int|null',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'uuid',
                            'string',
                        ],
                    ],
                    SignatureConfig::METHOD_RETURN_TYPES => [
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass1',
                            'getId',
                            'int|null',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass1',
                            'setId',
                            'void',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'getUuid',
                            'string',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'getValue',
                            'int',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'setUuid',
                            'void',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'setValue',
                            'void',
                        ],
                    ],
                    SignatureConfig::METHOD_PARAM_TYPES => [
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass1',
                            'setId',
                            0,
                            'int|null',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'setUuid',
                            0,
                            'string',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
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
