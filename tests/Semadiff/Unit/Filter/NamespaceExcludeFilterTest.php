<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Filter;

use Oro\UpgradeToolkit\Semadiff\Filter\NamespaceExcludeFilter;
use PHPUnit\Framework\TestCase;

final class NamespaceExcludeFilterTest extends TestCase
{
    public function testPrefixPattern(): void
    {
        $filter = new NamespaceExcludeFilter(['DT\Bundle\TestBundle\*']);

        $this->assertTrue($filter->isExcluded('DT\Bundle\TestBundle\SomeTest'));
        $this->assertTrue($filter->isExcluded('DT\Bundle\TestBundle\Sub\Deep'));
        $this->assertFalse($filter->isExcluded('DT\Bundle\OtherBundle\Service'));
    }

    public function testMiddleWildcard(): void
    {
        $filter = new NamespaceExcludeFilter(['*\Tests\*']);

        $this->assertTrue($filter->isExcluded('App\Tests\Unit\FooTest'));
        $this->assertTrue($filter->isExcluded('Vendor\Bundle\Tests\Functional\BarTest'));
        $this->assertFalse($filter->isExcluded('App\Service\FooService'));
    }

    public function testExactMatch(): void
    {
        $filter = new NamespaceExcludeFilter(['App\Service\Foo']);

        $this->assertTrue($filter->isExcluded('App\Service\Foo'));
        $this->assertFalse($filter->isExcluded('App\Service\FooBar'));
        $this->assertFalse($filter->isExcluded('App\Service\Foo\Sub'));
    }

    public function testMultiplePatterns(): void
    {
        $filter = new NamespaceExcludeFilter([
            'DT\Bundle\TestBundle\*',
            '*\Tests\*',
        ]);

        $this->assertTrue($filter->isExcluded('DT\Bundle\TestBundle\Foo'));
        $this->assertTrue($filter->isExcluded('App\Tests\Unit\Bar'));
        $this->assertFalse($filter->isExcluded('App\Service\Baz'));
    }

    public function testEmptyPatternsExcludeNothing(): void
    {
        $filter = new NamespaceExcludeFilter([]);

        $this->assertFalse($filter->isExcluded('Anything\Goes'));
    }

    public function testBlankPatternsIgnored(): void
    {
        $filter = new NamespaceExcludeFilter(['', '  ', 'App\Foo']);

        $this->assertTrue($filter->isExcluded('App\Foo'));
        $this->assertFalse($filter->isExcluded('App\Bar'));
    }

    public function testFromString(): void
    {
        $filter = NamespaceExcludeFilter::fromString('DT\Bundle\TestBundle\*, *\Tests\*');

        $this->assertTrue($filter->hasPatterns());
        $this->assertTrue($filter->isExcluded('DT\Bundle\TestBundle\Foo'));
        $this->assertTrue($filter->isExcluded('App\Tests\Unit\Bar'));
        $this->assertFalse($filter->isExcluded('App\Service\Baz'));
    }

    public function testFromStringNull(): void
    {
        $filter = NamespaceExcludeFilter::fromString(null);

        $this->assertFalse($filter->hasPatterns());
    }

    public function testFromStringEmpty(): void
    {
        $filter = NamespaceExcludeFilter::fromString('');

        $this->assertFalse($filter->hasPatterns());
    }

    public function testFilterList(): void
    {
        $filter = new NamespaceExcludeFilter(['*\Tests\*']);

        $input = [
            'App\Service\Foo',
            'App\Tests\Unit\FooTest',
            'App\Service\Bar',
            'Vendor\Tests\Fixture',
        ];

        $this->assertSame(['App\Service\Foo', 'App\Service\Bar'], $filter->filterList($input));
    }

    public function testFilterGroupedRemovesExcludedTargets(): void
    {
        $filter = new NamespaceExcludeFilter(['*\Tests\*']);

        $grouped = [
            'App\Service\Foo' => ['App\Controller\A', 'App\Tests\B'],
            'App\Tests\Base' => ['App\Tests\Sub\C'],
        ];

        $result = $filter->filterGrouped($grouped);

        // Second target removed entirely (excluded key)
        // First target: the excluded dependent is stripped
        $this->assertSame([
            'App\Service\Foo' => ['App\Controller\A'],
        ], $result);
    }

    public function testFilterGroupedRemovesEntryWhenAllDependentsExcluded(): void
    {
        $filter = new NamespaceExcludeFilter(['*\Tests\*']);

        $grouped = [
            'App\Service\Foo' => ['App\Tests\A', 'App\Tests\B'],
        ];

        $this->assertSame([], $filter->filterGrouped($grouped));
    }

    public function testSuffixPattern(): void
    {
        $filter = new NamespaceExcludeFilter(['*Test']);

        $this->assertTrue($filter->isExcluded('App\Tests\Unit\FooTest'));
        $this->assertFalse($filter->isExcluded('App\Tests\Unit\FooTestHelper'));
    }

    public function testHasPatterns(): void
    {
        $this->assertTrue((new NamespaceExcludeFilter(['Foo\*']))->hasPatterns());
        $this->assertFalse((new NamespaceExcludeFilter([]))->hasPatterns());
    }
}
