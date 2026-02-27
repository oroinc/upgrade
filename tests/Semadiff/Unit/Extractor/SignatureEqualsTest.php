<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Extractor;

use Oro\UpgradeToolkit\Semadiff\Extractor\ConstantInfo;
use Oro\UpgradeToolkit\Semadiff\Extractor\MethodInfo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SignatureEqualsTest extends TestCase
{
    /**
     * @return iterable<string, array{MethodInfo, MethodInfo, bool}>
     */
    public static function methodInfoSignatureProvider(): iterable
    {
        yield 'same isFinal' => [
            new MethodInfo('foo', 'public', false, false, true, [], 'string', 'hash1'),
            new MethodInfo('foo', 'public', false, false, true, [], 'string', 'hash2'),
            true,
        ];
        yield 'differs on isFinal' => [
            new MethodInfo('foo', 'public', false, false, true, [], 'string', 'hash1'),
            new MethodInfo('foo', 'public', false, false, false, [], 'string', 'hash1'),
            false,
        ];
        yield 'other fields match' => [
            new MethodInfo('foo', 'protected', true, false, false, [
                ['name' => 'x', 'type' => 'int', 'variadic' => false, 'byRef' => false],
            ], 'void', 'bodyHash1'),
            new MethodInfo('foo', 'protected', true, false, false, [
                ['name' => 'x', 'type' => 'int', 'variadic' => false, 'byRef' => false],
            ], 'void', 'bodyHash2'),
            true,
        ];
    }

    #[DataProvider('methodInfoSignatureProvider')]
    public function testMethodInfoSignatureEquals(MethodInfo $a, MethodInfo $b, bool $expected): void
    {
        $this->assertSame($expected, $a->signatureEquals($b));
    }

    /**
     * @return iterable<string, array{ConstantInfo, ConstantInfo, bool}>
     */
    public static function constantInfoSignatureProvider(): iterable
    {
        yield 'same isFinal' => [
            new ConstantInfo('X', 'string', 'valHash1', 'public', true),
            new ConstantInfo('X', 'string', 'valHash2', 'public', true),
            true,
        ];
        yield 'differs on isFinal' => [
            new ConstantInfo('X', 'string', 'valHash', 'public', true),
            new ConstantInfo('X', 'string', 'valHash', 'public', false),
            false,
        ];
    }

    #[DataProvider('constantInfoSignatureProvider')]
    public function testConstantInfoSignatureEquals(ConstantInfo $a, ConstantInfo $b, bool $expected): void
    {
        $this->assertSame($expected, $a->signatureEquals($b));
    }
}
