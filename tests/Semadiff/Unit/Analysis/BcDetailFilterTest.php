<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Analysis;

use Oro\UpgradeToolkit\Semadiff\Analysis\BcDetailFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BcDetailFilterTest extends TestCase
{
    private BcDetailFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new BcDetailFilter();
    }

    // ── filterBcDetails ─────────────────────────────────────────────────

    /**
     * @return iterable<string, array{string}>
     */
    public static function bcKeptDetailsProvider(): iterable
    {
        yield 'param type changed' => ['Method param type changed: Foo::bar'];
        yield 'required param added' => ['Method param added (required): Foo::bar'];
        yield 'return type changed' => ['Method return type changed: Foo::bar'];
        yield 'visibility tightened' => ['Method visibility tightened: Foo::bar'];
        yield 'made final' => ['Method made final: Foo::bar'];
        yield 'made abstract' => ['Method made abstract: Foo::bar'];
    }

    #[DataProvider('bcKeptDetailsProvider')]
    public function testFilterBcDetailsKeepsBcBreakingDetail(string $detail): void
    {
        $details = [$detail];
        $this->assertSame($details, $this->filter->filterBcDetails($details));
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function bcRemovedDetailsProvider(): iterable
    {
        yield 'optional param added' => [['Method param added (optional): Foo::bar']];
        yield 'return type added' => [['Method return type added: Foo::bar']];
        yield 'body changed' => [['Method body changed: Foo::bar']];
        yield 'visibility loosened' => [['Method visibility loosened: Foo::bar']];
        yield 'property type added' => [['Property type added: Foo::$bar']];
        yield 'additions' => [[
            'Method added: Foo::newMethod',
            'Property added: Foo::$newProp',
            'Constant added: Foo::NEW_CONST',
            'Class added: NewClass',
        ]];
    }

    #[DataProvider('bcRemovedDetailsProvider')]
    public function testFilterBcDetailsRemovesNonBcDetail(array $details): void
    {
        $this->assertSame([], $this->filter->filterBcDetails($details));
    }

    public function testFilterBcDetailsMixedDetails(): void
    {
        $details = [
            'Method body changed: Foo::bar',
            'Method param type changed: Foo::bar',
            'Method visibility loosened: Foo::baz',
            'Method made final: Foo::qux',
            'Property default value changed: Foo::$x',
            'Constructor changed: Foo::__construct',
        ];
        // Constructor changed removed — no other BC detail references __construct
        $expected = [
            'Method param type changed: Foo::bar',
            'Method made final: Foo::qux',
        ];
        $this->assertSame($expected, $this->filter->filterBcDetails($details));
    }

    public function testFilterBcDetailsKeepsConstructorWithBcParamChanges(): void
    {
        $details = [
            'Constructor changed: Foo::__construct',
            'Method param type changed: Foo::__construct',
        ];
        $expected = [
            'Constructor changed: Foo::__construct',
            'Method param type changed: Foo::__construct',
        ];
        $this->assertSame($expected, $this->filter->filterBcDetails($details));
    }

    public function testFilterBcDetailsRemovesConstructorWithOnlyTypeAdded(): void
    {
        $details = [
            'Constructor changed: Foo::__construct',
            'Method param type added: Foo::__construct',
        ];
        $this->assertSame([], $this->filter->filterBcDetails($details));
    }

    // ── filterRemovedDetails ────────────────────────────────────────────

    public function testFilterRemovedDetailsKeepsRemovedMembers(): void
    {
        $details = [
            'Method removed: Foo::bar',
            'Property removed: Foo::$baz',
            'Constant removed: Foo::QUX',
        ];
        $this->assertSame($details, $this->filter->filterRemovedDetails($details));
    }

    public function testFilterRemovedDetailsExcludesNonRemoved(): void
    {
        $details = [
            'Method param type changed: Foo::bar',
            'Method removed: Foo::baz',
            'Property added: Foo::$new',
        ];
        $this->assertSame(['Method removed: Foo::baz'], $this->filter->filterRemovedDetails($details));
    }

    // ── extractChangedMethods ───────────────────────────────────────────

    public function testExtractChangedMethodsFromParamChanges(): void
    {
        $bcDetails = [
            'Method param type changed: Foo::save',
            'Method param added (required): Foo::load',
        ];
        $result = $this->filter->extractChangedMethods($bcDetails);
        $this->assertEqualsCanonicalizing(['save', 'load'], $result['methods']);
        $this->assertFalse($result['constructorChanged']);
    }

    public function testExtractChangedMethodsDetectsConstructor(): void
    {
        $bcDetails = [
            'Constructor changed: Foo::__construct',
            'Method param type changed: Foo::__construct',
        ];
        $result = $this->filter->extractChangedMethods($bcDetails);
        $this->assertContains('__construct', $result['methods']);
        $this->assertTrue($result['constructorChanged']);
    }

    public function testExtractChangedMethodsConstructorAddedWhenOnlyConstructorChanged(): void
    {
        $bcDetails = [
            'Constructor changed: Foo::__construct',
        ];
        $result = $this->filter->extractChangedMethods($bcDetails);
        $this->assertSame(['__construct'], $result['methods']);
        $this->assertTrue($result['constructorChanged']);
    }

    public function testExtractChangedMethodsDeduplicates(): void
    {
        $bcDetails = [
            'Method param type changed: Foo::save',
            'Method param renamed: Foo::save',
        ];
        $result = $this->filter->extractChangedMethods($bcDetails);
        $this->assertSame(['save'], $result['methods']);
    }

    public function testExtractChangedMethodsFromRemoved(): void
    {
        $bcDetails = [
            'Method removed: Foo::oldMethod',
        ];
        $result = $this->filter->extractChangedMethods($bcDetails);
        $this->assertSame(['oldMethod'], $result['methods']);
    }
}
