<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Analysis;

use Oro\UpgradeToolkit\Semadiff\Analysis\ResolutionChecker;
use Oro\UpgradeToolkit\Semadiff\Extractor\MethodInfo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResolutionCheckerTest extends TestCase
{
    private ResolutionChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new ResolutionChecker();
    }

    // ── paramsCompatible ────────────────────────────────────────────────

    /**
     * @return iterable<string, array{list<array<string, mixed>>, list<array<string, mixed>>, bool}>
     */
    public static function paramsCompatibleProvider(): iterable
    {
        yield 'matching signatures' => [
            [
                ['name' => 'name', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
                ['name' => 'priority', 'type' => 'int', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ],
            [
                ['name' => 'name', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
                ['name' => 'priority', 'type' => 'int', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ],
            true,
        ];
        yield 'project missing required param' => [
            [
                ['name' => 'name', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
                ['name' => 'priority', 'type' => 'int', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ],
            [
                ['name' => 'name', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ],
            false,
        ];
        yield 'type mismatch' => [
            [
                ['name' => 'name', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ],
            [
                ['name' => 'name', 'type' => 'int', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ],
            false,
        ];
        yield 'optional params ignored' => [
            [
                ['name' => 'name', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
                ['name' => 'opts', 'type' => 'array', 'variadic' => false, 'byRef' => false, 'hasDefault' => true],
            ],
            [
                ['name' => 'name', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
                ['name' => 'opts', 'type' => 'array', 'variadic' => false, 'byRef' => false, 'hasDefault' => true],
            ],
            true,
        ];
        yield 'no types' => [
            [
                ['name' => 'name', 'type' => null, 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ],
            [
                ['name' => 'name', 'type' => null, 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ],
            true,
        ];
        yield 'union type normalization' => [
            [
                ['name' => 'value', 'type' => 'string|int', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ],
            [
                ['name' => 'value', 'type' => 'int|string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ],
            true,
        ];
    }

    #[DataProvider('paramsCompatibleProvider')]
    public function testParamsCompatible(array $vendorParams, array $projectParams, bool $expected): void
    {
        $vendor = new MethodInfo('save', 'public', false, false, false, $vendorParams, 'void', '');
        $project = new MethodInfo('save', 'public', false, false, false, $projectParams, 'void', '');

        $this->assertSame($expected, $this->checker->paramsCompatible($vendor, $project));
    }

    // ── buildParamDiff ──────────────────────────────────────────────────

    public function testBuildParamDiffShowsAddedParam(): void
    {
        $vendor = new MethodInfo('save', 'public', false, false, false, [
            ['name' => 'name', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ['name' => 'priority', 'type' => 'int', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
        ], 'void', '');

        $project = new MethodInfo('save', 'public', false, false, false, [
            ['name' => 'name', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
        ], 'void', '');

        $diff = $this->checker->buildParamDiff($vendor, $project);
        $this->assertStringContainsString('+    int $priority,', $diff);
        $this->assertStringContainsString('string $name,', $diff);
    }

    public function testBuildParamDiffShowsTypeChange(): void
    {
        $vendor = new MethodInfo('save', 'public', false, false, false, [
            ['name' => 'value', 'type' => 'int', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
        ], 'void', '');

        $project = new MethodInfo('save', 'public', false, false, false, [
            ['name' => 'value', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
        ], 'void', '');

        $diff = $this->checker->buildParamDiff($vendor, $project);
        $this->assertStringContainsString('-    string $value,', $diff);
        $this->assertStringContainsString('+    int $value,', $diff);
    }

    // ── memberReferencedInCode ──────────────────────────────────────────

    public function testMemberReferencedInCodeFindsMethod(): void
    {
        $code = '<?php class Foo { public function test() { $this->oldMethod(); } }';
        $this->assertTrue($this->checker->memberReferencedInCode($code, 'oldMethod'));
    }

    public function testMemberReferencedInCodeNotFound(): void
    {
        $code = '<?php class Foo { public function test() { $this->newMethod(); } }';
        $this->assertFalse($this->checker->memberReferencedInCode($code, 'oldMethod'));
    }

    public function testMemberReferencedInCodeFindsProperty(): void
    {
        $code = '<?php class Foo { public function test() { echo $this->$oldProp; } }';
        $this->assertTrue($this->checker->memberReferencedInCode($code, '$oldProp'));
    }

    public function testMemberReferencedInCodeFindsConstant(): void
    {
        $code = '<?php class Foo { public function test() { echo self::OLD_CONST; } }';
        $this->assertTrue($this->checker->memberReferencedInCode($code, 'OLD_CONST'));
    }

    public function testMemberReferencedInCodePropertyNotMatchedAsSubstring(): void
    {
        // $env should NOT match $environment
        $code = '<?php class Foo { public function test(Environment $environment) {} }';
        $this->assertFalse($this->checker->memberReferencedInCode($code, '$env'));
    }

    public function testMemberReferencedInCodeMethodNotMatchedAsSubstring(): void
    {
        // "get" should NOT match "getConnection"
        $code = '<?php class Foo { public function test() { $this->getConnection(); } }';
        $this->assertFalse($this->checker->memberReferencedInCode($code, 'get'));
    }

    // ── checkNamedArgs ─────────────────────────────────────────────────

    public function testCheckNamedArgsAllMatch(): void
    {
        $vendor = new MethodInfo('__construct', 'public', false, false, false, [
            ['name' => 'pattern', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ['name' => 'flags', 'type' => 'int', 'variadic' => false, 'byRef' => false, 'hasDefault' => true],
        ], null, '');

        $result = $this->checker->checkNamedArgs($vendor, 'App\\MyClass', '__construct', 'instance_call', ['pattern']);

        $this->assertTrue((bool) $result['resolved']);
        $this->assertStringContainsString('Named args OK', $result['note']);
    }

    public function testCheckNamedArgsUnknownParam(): void
    {
        $vendor = new MethodInfo('__construct', 'public', false, false, false, [
            ['name' => 'pattern', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
        ], null, '');

        $result = $this->checker->checkNamedArgs($vendor, 'App\\MyClass', '__construct', 'instance_call', ['pattern', 'nonexistent']);

        $this->assertFalse((bool) $result['resolved']);
        $this->assertStringContainsString('unknown param(s): nonexistent', $result['note']);
    }

    public function testCheckNamedArgsMissingRequired(): void
    {
        $vendor = new MethodInfo('__construct', 'public', false, false, false, [
            ['name' => 'pattern', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ['name' => 'subject', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
        ], null, '');

        $result = $this->checker->checkNamedArgs($vendor, 'App\\MyClass', '__construct', 'instance_call', ['pattern']);

        $this->assertFalse((bool) $result['resolved']);
        $this->assertStringContainsString('missing required: subject', $result['note']);
    }

    public function testCheckNamedArgsOptionalOmitted(): void
    {
        $vendor = new MethodInfo('__construct', 'public', false, false, false, [
            ['name' => 'pattern', 'type' => 'string', 'variadic' => false, 'byRef' => false, 'hasDefault' => false],
            ['name' => 'flags', 'type' => 'int', 'variadic' => false, 'byRef' => false, 'hasDefault' => true],
            ['name' => 'offset', 'type' => 'int', 'variadic' => false, 'byRef' => false, 'hasDefault' => true],
        ], null, '');

        $result = $this->checker->checkNamedArgs($vendor, 'App\\MyClass', '__construct', 'instance_call', ['pattern']);

        $this->assertTrue((bool) $result['resolved']);
    }

    // ── checkAll integration ────────────────────────────────────────────

    public function testCheckAllWithEmptyInputs(): void
    {
        $result = $this->checker->checkAll([], [], [], [], [], [], '/tmp', ['/tmp']);

        $this->assertSame([], $result['vendorItems']);
        $this->assertSame([], $result['deletedItems']);
        $this->assertSame(0, $result['totalItems']);
        $this->assertSame(0, $result['resolvedItems']);
    }
}
