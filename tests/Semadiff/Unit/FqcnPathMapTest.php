<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit;

use Oro\UpgradeToolkit\Semadiff\FqcnPathMap;
use PHPUnit\Framework\TestCase;

final class FqcnPathMapTest extends TestCase
{
    public function testSetAndGet(): void
    {
        $map = new FqcnPathMap();
        $map->set('App\\Foo', '/path/to/Foo.php');

        $this->assertSame('/path/to/Foo.php', $map->get('App\\Foo'));
    }

    public function testGetReturnsNullForUnknown(): void
    {
        $map = new FqcnPathMap();

        $this->assertNull($map->get('App\\Unknown'));
    }

    public function testSetOverwritesPrevious(): void
    {
        $map = new FqcnPathMap();
        $map->set('App\\Foo', '/old/Foo.php');
        $map->set('App\\Foo', '/new/Foo.php');

        $this->assertSame('/new/Foo.php', $map->get('App\\Foo'));
    }

    public function testMerge(): void
    {
        $map1 = new FqcnPathMap();
        $map1->set('App\\Foo', '/path/to/Foo.php');

        $map2 = new FqcnPathMap();
        $map2->set('App\\Bar', '/path/to/Bar.php');
        $map2->set('App\\Foo', '/other/Foo.php');

        $map1->merge($map2);

        $this->assertSame('/other/Foo.php', $map1->get('App\\Foo'));
        $this->assertSame('/path/to/Bar.php', $map1->get('App\\Bar'));
    }

    public function testCount(): void
    {
        $map = new FqcnPathMap();
        $this->assertSame(0, $map->count());

        $map->set('App\\Foo', '/path/to/Foo.php');
        $this->assertSame(1, $map->count());

        $map->set('App\\Bar', '/path/to/Bar.php');
        $this->assertSame(2, $map->count());
    }

    public function testCountAfterOverwrite(): void
    {
        $map = new FqcnPathMap();
        $map->set('App\\Foo', '/old/Foo.php');
        $map->set('App\\Foo', '/new/Foo.php');

        $this->assertSame(1, $map->count());
    }
}
