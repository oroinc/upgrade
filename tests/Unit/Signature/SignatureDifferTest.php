<?php

namespace Oro\Tests\Unit\Signature;

use Oro\Rector\Signature\SignatureConfig;
use Oro\Rector\Signature\SignatureDiffer;
use PHPUnit\Framework\TestCase;

class SignatureDifferTest extends TestCase
{
    private SignatureDiffer $differ;

    protected function setUp(): void
    {
        $this->differ = new SignatureDiffer();
    }

    /**
     * @dataProvider diffDataProvider
     */
    public function testDiff(array $oldSignatures, array $expected)
    {
        $actual = $this->differ->diff($oldSignatures);
        $this->assertSame($expected, $actual);
    }

    public function diffDataProvider(): array
    {
        return [
            [
                [
                    SignatureConfig::PROPERTY_TYPES => [],
                    SignatureConfig::METHOD_RETURN_TYPES => [],
                    SignatureConfig::METHOD_PARAM_TYPES => [],
                ],
                [
                    SignatureConfig::PROPERTY_TYPES => [],
                    SignatureConfig::METHOD_RETURN_TYPES => [],
                    SignatureConfig::METHOD_PARAM_TYPES => [],
                ],
            ],
            [
                [
                    SignatureConfig::PROPERTY_TYPES => [
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'uuid',
                            'int',
                        ],
                    ],
                    SignatureConfig::METHOD_RETURN_TYPES => [
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'getUuid',
                            'int',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'getValue',
                            'int',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'setUuid',
                            'int',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'setValue',
                            'void',
                        ],
                    ],
                    SignatureConfig::METHOD_PARAM_TYPES => [
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'setUuid',
                            0,
                            'int',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'setValue',
                            0,
                            'int',
                        ],
                    ],
                ],
                [
                    SignatureConfig::PROPERTY_TYPES => [
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'uuid',
                            'int',
                        ],
                    ],
                    SignatureConfig::METHOD_RETURN_TYPES => [
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'getUuid',
                            'int',
                        ],
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'setUuid',
                            'int',
                        ],
                    ],
                    SignatureConfig::METHOD_PARAM_TYPES => [
                        [
                            'Oro\Tests\Unit\Fixtures\TestClass2',
                            'setUuid',
                            0,
                            'int',
                        ],
                    ],
                ],
            ],
        ];
    }
}
