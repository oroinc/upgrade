<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Comparator;

use Oro\UpgradeToolkit\Semadiff\Comparator\FileComparator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileComparatorGranularTest extends TestCase
{
    private FileComparator $comparator;

    protected function setUp(): void
    {
        $this->comparator = new FileComparator();
    }

    // ── Class-level tests ──────────────────────────────────────────────

    public function testClassMadeFinal(): void
    {
        $before = '<?php class Foo {}';
        $after = '<?php final class Foo {}';

        $result = $this->comparator->compare($before, $after);
        $this->assertContains('Class made final: Foo', $result->details);
        $this->assertTrue($result->signatureChanged);
    }

    public function testClassAlreadyFinal(): void
    {
        $before = '<?php final class Foo { public function bar(): void {} }';
        $after = '<?php final class Foo { public function bar(): void {} }';

        $result = $this->comparator->compare($before, $after);
        $this->assertNotContains('Class made final: Foo', $result->details);
    }

    // ── Method return type tests ───────────────────────────────────────

    public function testMethodReturnTypeAdded(): void
    {
        $before = '<?php class Foo { public function bar() {} }';
        $after = '<?php class Foo { public function bar(): string { return ""; } }';

        $result = $this->comparator->compare($before, $after);
        $this->assertContains('Method return type added: Foo::bar', $result->details);
    }

    public function testMethodReturnTypeChanged(): void
    {
        $before = '<?php class Foo { public function bar(): string { return ""; } }';
        $after = '<?php class Foo { public function bar(): int { return 0; } }';

        $result = $this->comparator->compare($before, $after);
        $this->assertContains('Method return type changed: Foo::bar', $result->details);
    }

    // ── Method visibility tests ────────────────────────────────────────

    public function testMethodVisibilityTightened(): void
    {
        $before = '<?php class Foo { public function bar(): void {} }';
        $after = '<?php class Foo { protected function bar(): void {} }';

        $result = $this->comparator->compare($before, $after);
        $this->assertContains('Method visibility tightened: Foo::bar', $result->details);
    }

    public function testMethodVisibilityLoosened(): void
    {
        $before = '<?php class Foo { protected function bar(): void {} }';
        $after = '<?php class Foo { public function bar(): void {} }';

        $result = $this->comparator->compare($before, $after);
        $this->assertContains('Method visibility loosened: Foo::bar', $result->details);
    }

    // ── Method abstract/final/static tests ─────────────────────────────

    public function testMethodMadeAbstract(): void
    {
        $before = '<?php abstract class Foo { public function bar(): void {} }';
        $after = '<?php abstract class Foo { abstract public function bar(): void; }';

        $result = $this->comparator->compare($before, $after);
        $this->assertContains('Method made abstract: Foo::bar', $result->details);
    }

    public function testMethodMadeFinal(): void
    {
        $before = '<?php class Foo { public function bar(): void {} }';
        $after = '<?php class Foo { final public function bar(): void {} }';

        $result = $this->comparator->compare($before, $after);
        $this->assertContains('Method made final: Foo::bar', $result->details);
    }

    public function testMethodStaticChanged(): void
    {
        $before = '<?php class Foo { public function bar(): void {} }';
        $after = '<?php class Foo { public static function bar(): void {} }';

        $result = $this->comparator->compare($before, $after);
        $this->assertContains('Method static changed: Foo::bar', $result->details);
    }

    // ── Param change tests ─────────────────────────────────────────────

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function paramChangeProvider(): iterable
    {
        yield 'required param added' => [
            '<?php class Foo { public function bar(): void {} }',
            '<?php class Foo { public function bar(string $name): void {} }',
            'Method param added (required): Foo::bar',
        ];
        yield 'optional param added' => [
            '<?php class Foo { public function bar(): void {} }',
            '<?php class Foo { public function bar(string $name = ""): void {} }',
            'Method param added (optional): Foo::bar',
        ];
        yield 'variadic param added' => [
            '<?php class Foo { public function bar(): void {} }',
            '<?php class Foo { public function bar(string ...$items): void {} }',
            'Method param added (optional): Foo::bar',
        ];
        yield 'param removed' => [
            '<?php class Foo { public function bar(string $name): void {} }',
            '<?php class Foo { public function bar(): void {} }',
            'Method param removed: Foo::bar',
        ];
        yield 'param type added' => [
            '<?php class Foo { public function bar($name): void {} }',
            '<?php class Foo { public function bar(string $name): void {} }',
            'Method param type added: Foo::bar',
        ];
        yield 'param type changed' => [
            '<?php class Foo { public function bar(string $name): void {} }',
            '<?php class Foo { public function bar(int $name): void {} }',
            'Method param type changed: Foo::bar',
        ];
        yield 'param renamed' => [
            '<?php class Foo { public function bar(string $name): void {} }',
            '<?php class Foo { public function bar(string $title): void {} }',
            'Method param renamed: Foo::bar',
        ];
        yield 'param modifier changed (variadic)' => [
            '<?php class Foo { public function bar(string $items): void {} }',
            '<?php class Foo { public function bar(string ...$items): void {} }',
            'Method param modifier changed: Foo::bar',
        ];
        yield 'param modifier changed (byRef)' => [
            '<?php class Foo { public function bar(string $name): void {} }',
            '<?php class Foo { public function bar(string &$name): void {} }',
            'Method param modifier changed: Foo::bar',
        ];
    }

    #[DataProvider('paramChangeProvider')]
    public function testParamChange(string $before, string $after, string $expectedDetail): void
    {
        $result = $this->comparator->compare($before, $after);
        $this->assertContains($expectedDetail, $result->details);
    }

    // ── Constructor test ───────────────────────────────────────────────

    public function testConstructorChanged(): void
    {
        $before = '<?php class Foo { public function __construct(string $name) {} }';
        $after = '<?php class Foo { public function __construct(string $name, int $age) {} }';

        $result = $this->comparator->compare($before, $after);
        $this->assertContains('Constructor changed: Foo::__construct', $result->details);
    }

    // ── Property tests ─────────────────────────────────────────────────

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function propertyChangeProvider(): iterable
    {
        yield 'type added' => [
            '<?php class Foo { public $bar; }',
            '<?php class Foo { public string $bar; }',
            'Property type added: Foo::$bar',
        ];
        yield 'type changed' => [
            '<?php class Foo { public string $bar; }',
            '<?php class Foo { public int $bar; }',
            'Property type changed: Foo::$bar',
        ];
        yield 'visibility tightened' => [
            '<?php class Foo { public string $bar; }',
            '<?php class Foo { private string $bar; }',
            'Property visibility tightened: Foo::$bar',
        ];
        yield 'visibility loosened' => [
            '<?php class Foo { private string $bar; }',
            '<?php class Foo { public string $bar; }',
            'Property visibility loosened: Foo::$bar',
        ];
        yield 'made readonly' => [
            '<?php class Foo { public string $bar; }',
            '<?php class Foo { public readonly string $bar; }',
            'Property made readonly: Foo::$bar',
        ];
        yield 'static changed' => [
            '<?php class Foo { public string $bar; }',
            '<?php class Foo { public static string $bar; }',
            'Property static changed: Foo::$bar',
        ];
    }

    #[DataProvider('propertyChangeProvider')]
    public function testPropertyChange(string $before, string $after, string $expectedDetail): void
    {
        $result = $this->comparator->compare($before, $after);
        $this->assertContains($expectedDetail, $result->details);
    }

    // ── Constant tests ─────────────────────────────────────────────────

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function constantChangeProvider(): iterable
    {
        yield 'made final' => [
            '<?php class Foo { public const BAR = 1; }',
            '<?php class Foo { final public const BAR = 1; }',
            'Constant made final: Foo::BAR',
        ];
        yield 'type changed' => [
            '<?php class Foo { public const string BAR = "x"; }',
            '<?php class Foo { public const int BAR = 1; }',
            'Constant type changed: Foo::BAR',
        ];
        yield 'visibility changed' => [
            '<?php class Foo { public const BAR = 1; }',
            '<?php class Foo { protected const BAR = 1; }',
            'Constant visibility changed: Foo::BAR',
        ];
    }

    #[DataProvider('constantChangeProvider')]
    public function testConstantChange(string $before, string $after, string $expectedDetail): void
    {
        $result = $this->comparator->compare($before, $after);
        $this->assertContains($expectedDetail, $result->details);
    }

    // ── Member add/remove tests ────────────────────────────────────────

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function memberAddRemoveProvider(): iterable
    {
        yield 'method added' => [
            '<?php class Foo {}',
            '<?php class Foo { public function bar(): void {} }',
            'Method added: Foo::bar',
        ];
        yield 'method removed' => [
            '<?php class Foo { public function bar(): void {} }',
            '<?php class Foo {}',
            'Method removed: Foo::bar',
        ];
        yield 'property added' => [
            '<?php class Foo {}',
            '<?php class Foo { public string $bar; }',
            'Property added: Foo::$bar',
        ];
        yield 'property removed' => [
            '<?php class Foo { public string $bar; }',
            '<?php class Foo {}',
            'Property removed: Foo::$bar',
        ];
        yield 'constant added' => [
            '<?php class Foo {}',
            '<?php class Foo { public const BAR = 1; }',
            'Constant added: Foo::BAR',
        ];
        yield 'constant removed' => [
            '<?php class Foo { public const BAR = 1; }',
            '<?php class Foo {}',
            'Constant removed: Foo::BAR',
        ];
    }

    #[DataProvider('memberAddRemoveProvider')]
    public function testMemberAddOrRemove(string $before, string $after, string $expectedDetail): void
    {
        $result = $this->comparator->compare($before, $after);
        $this->assertContains($expectedDetail, $result->details);
        $this->assertTrue($result->membersAddedOrRemoved);
    }

    // ── Combination/edge tests ─────────────────────────────────────────

    public function testMultipleChangesProduceMultipleDetails(): void
    {
        $before = '<?php
class Foo {
    public function __construct(string $name) {}
    public function bar(): void {}
    public string $baz;
}';
        $after = '<?php
class Foo {
    public function __construct(string $name, int $age) {}
    public function bar(int $x): void {}
    public int $baz;
}';

        $result = $this->comparator->compare($before, $after);
        $this->assertContains('Constructor changed: Foo::__construct', $result->details);
        $this->assertContains('Method param added (required): Foo::bar', $result->details);
        $this->assertContains('Property type changed: Foo::$baz', $result->details);
    }

    public function testNoChangesProduceEmptyDetails(): void
    {
        $code = '<?php
class Foo {
    public function bar(): void {}
    public string $baz;
    public const X = 1;
}';

        $result = $this->comparator->compare($code, $code);
        $this->assertSame([], $result->details);
    }
}
