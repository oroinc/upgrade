<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Resolver;

use Oro\UpgradeToolkit\Semadiff\Resolver\DependencyResolver;
use Oro\UpgradeToolkit\Tests\Semadiff\Support\TempDirTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DependencyResolverTest extends TestCase
{
    use TempDirTrait;

    private DependencyResolver $resolver;

    protected function setUp(): void
    {
        $this->setUpTempDir();
        $this->resolver = new DependencyResolver();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDir();
    }

    public function testClassExtendingTarget(): void
    {
        $this->createFile('Base.php', <<<'PHP'
        <?php
        namespace Vendor\Lib;
        class Base {}
        PHP);

        $this->createFile('Child.php', <<<'PHP'
        <?php
        namespace App;
        use Vendor\Lib\Base;
        class Child extends Base {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Lib\Base']);

        $this->assertSame(['Vendor\Lib\Base' => ['App\Child']], $result->extends);
        $this->assertSame([], $result->implements);
        $this->assertSame([], $result->traits);
        $this->assertSame([], $result->uses);
    }

    public function testClassImplementingTarget(): void
    {
        $this->createFile('MyInterface.php', <<<'PHP'
        <?php
        namespace Vendor\Contract;
        interface MyInterface {}
        PHP);

        $this->createFile('Impl.php', <<<'PHP'
        <?php
        namespace App;
        use Vendor\Contract\MyInterface;
        class Impl implements MyInterface {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Contract\MyInterface']);

        $this->assertSame([], $result->extends);
        $this->assertSame(['Vendor\Contract\MyInterface' => ['App\Impl']], $result->implements);
        $this->assertSame([], $result->traits);
        $this->assertSame([], $result->uses);
    }

    public function testClassUsingTargetTrait(): void
    {
        $this->createFile('MyTrait.php', <<<'PHP'
        <?php
        namespace Vendor\Mixin;
        trait MyTrait {}
        PHP);

        $this->createFile('Consumer.php', <<<'PHP'
        <?php
        namespace App;
        use Vendor\Mixin\MyTrait;
        class Consumer { use MyTrait; }
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Mixin\MyTrait']);

        $this->assertSame([], $result->extends);
        $this->assertSame([], $result->implements);
        $this->assertSame(['Vendor\Mixin\MyTrait' => ['App\Consumer']], $result->traits);
        $this->assertSame([], $result->uses);
    }

    public function testUnrelatedClassNotReturned(): void
    {
        $this->createFile('Unrelated.php', <<<'PHP'
        <?php
        namespace App;
        class Unrelated {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Lib\Base']);

        $this->assertSame([], $result->extends);
        $this->assertSame([], $result->implements);
        $this->assertSame([], $result->traits);
        $this->assertSame([], $result->uses);
    }

    public function testEnumImplementingTarget(): void
    {
        $this->createFile('StatusInterface.php', <<<'PHP'
        <?php
        namespace Vendor\Contract;
        interface StatusInterface {}
        PHP);

        $this->createFile('Status.php', <<<'PHP'
        <?php
        namespace App;
        use Vendor\Contract\StatusInterface;
        enum Status: string implements StatusInterface {
            case Active = 'active';
        }
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Contract\StatusInterface']);

        $this->assertSame([], $result->extends);
        $this->assertSame(['Vendor\Contract\StatusInterface' => ['App\Status']], $result->implements);
        $this->assertSame([], $result->traits);
        $this->assertSame([], $result->uses);
    }

    public function testInterfaceExtendingTarget(): void
    {
        $this->createFile('ParentInterface.php', <<<'PHP'
        <?php
        namespace Vendor\Contract;
        interface ParentInterface {}
        PHP);

        $this->createFile('ChildInterface.php', <<<'PHP'
        <?php
        namespace App;
        use Vendor\Contract\ParentInterface;
        interface ChildInterface extends ParentInterface {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Contract\ParentInterface']);

        $this->assertSame(['Vendor\Contract\ParentInterface' => ['App\ChildInterface']], $result->extends);
        $this->assertSame([], $result->implements);
        $this->assertSame([], $result->traits);
        $this->assertSame([], $result->uses);
    }

    public function testInlineFqcnDetected(): void
    {
        $this->createFile('Inline.php', <<<'PHP'
        <?php
        namespace App;
        class Inline extends \Vendor\Lib\Base {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Lib\Base']);

        $this->assertSame(['Vendor\Lib\Base' => ['App\Inline']], $result->extends);
    }

    public function testClassMatchingMultipleTypes(): void
    {
        $this->createFile('Base.php', <<<'PHP'
        <?php
        namespace Vendor;
        class Base {}
        PHP);

        $this->createFile('MyInterface.php', <<<'PHP'
        <?php
        namespace Vendor;
        interface MyInterface {}
        PHP);

        $this->createFile('MyTrait.php', <<<'PHP'
        <?php
        namespace Vendor;
        trait MyTrait {}
        PHP);

        $this->createFile('Multi.php', <<<'PHP'
        <?php
        namespace App;
        use Vendor\Base;
        use Vendor\MyInterface;
        use Vendor\MyTrait;
        class Multi extends Base implements MyInterface { use MyTrait; }
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, [
            'Vendor\Base',
            'Vendor\MyInterface',
            'Vendor\MyTrait',
        ]);

        $this->assertSame(['Vendor\Base' => ['App\Multi']], $result->extends);
        $this->assertSame(['Vendor\MyInterface' => ['App\Multi']], $result->implements);
        $this->assertSame(['Vendor\MyTrait' => ['App\Multi']], $result->traits);
    }

    public function testResultsAreSortedWithinTarget(): void
    {
        $this->createFile('Z.php', <<<'PHP'
        <?php
        namespace App;
        class Z extends \Vendor\Base {}
        PHP);

        $this->createFile('A.php', <<<'PHP'
        <?php
        namespace App;
        class A extends \Vendor\Base {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Base']);

        $this->assertSame(['Vendor\Base' => ['App\A', 'App\Z']], $result->extends);
    }

    public function testTargetKeysAreSorted(): void
    {
        $this->createFile('ChildZ.php', <<<'PHP'
        <?php
        namespace App;
        class ChildZ extends \Vendor\Z {}
        PHP);

        $this->createFile('ChildA.php', <<<'PHP'
        <?php
        namespace App;
        class ChildA extends \Vendor\A {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Z', 'Vendor\A']);

        $this->assertSame(['Vendor\A', 'Vendor\Z'], array_keys($result->extends));
    }

    public function testMultipleDependentsGroupedUnderSameTarget(): void
    {
        $this->createFile('ChildA.php', <<<'PHP'
        <?php
        namespace App;
        class ChildA extends \Vendor\Base {}
        PHP);

        $this->createFile('ChildB.php', <<<'PHP'
        <?php
        namespace App;
        class ChildB extends \Vendor\Base {}
        PHP);

        $this->createFile('ChildC.php', <<<'PHP'
        <?php
        namespace App;
        class ChildC extends \Vendor\Other {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Base', 'Vendor\Other']);

        $this->assertSame([
            'Vendor\Base' => ['App\ChildA', 'App\ChildB'],
            'Vendor\Other' => ['App\ChildC'],
        ], $result->extends);
        $this->assertSame(3, $result->extendsCount());
    }

    public function testClassImplementingMultipleTargetInterfaces(): void
    {
        $this->createFile('Multi.php', <<<'PHP'
        <?php
        namespace App;
        class Multi implements \Vendor\FooInterface, \Vendor\BarInterface {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, [
            'Vendor\FooInterface',
            'Vendor\BarInterface',
        ]);

        $this->assertSame([
            'Vendor\BarInterface' => ['App\Multi'],
            'Vendor\FooInterface' => ['App\Multi'],
        ], $result->implements);
    }

    public function testNonPhpFilesIgnored(): void
    {
        $this->createFile('readme.txt', 'class Foo extends \Vendor\Base {}');

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Base']);

        $this->assertSame([], $result->extends);
    }

    public function testSubdirectoriesScanned(): void
    {
        $this->createFile('sub/deep/Child.php', <<<'PHP'
        <?php
        namespace App\Sub;
        class Child extends \Vendor\Base {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Base']);

        $this->assertSame(['Vendor\Base' => ['App\Sub\Child']], $result->extends);
    }

    public function testCountHelpers(): void
    {
        $this->createFile('A.php', <<<'PHP'
        <?php
        namespace App;
        class A extends \Vendor\Base implements \Vendor\Iface { use \Vendor\MyTrait; }
        PHP);

        $this->createFile('B.php', <<<'PHP'
        <?php
        namespace App;
        class B extends \Vendor\Base {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, [
            'Vendor\Base',
            'Vendor\Iface',
            'Vendor\MyTrait',
        ]);

        $this->assertSame(2, $result->extendsCount());
        $this->assertSame(1, $result->implementsCount());
        $this->assertSame(1, $result->traitsCount());
    }

    // ── Uses detection ─────────────────────────────────────────────────

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function usesDetectionProvider(): iterable
    {
        yield 'type hint' => [
            'Service.php',
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Lib\Logger;
            class Service {
                public function run(Logger $logger): void {}
            }
            PHP,
            'Vendor\Lib\Logger',
            'App\Service',
        ];
        yield 'new instantiation' => [
            'Factory.php',
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Lib\Widget;
            class Factory {
                public function create(): object { return new Widget(); }
            }
            PHP,
            'Vendor\Lib\Widget',
            'App\Factory',
        ];
        yield 'instanceof' => [
            'Checker.php',
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Contract\Checkable;
            class Checker {
                public function check(object $obj): bool { return $obj instanceof Checkable; }
            }
            PHP,
            'Vendor\Contract\Checkable',
            'App\Checker',
        ];
        yield 'static call' => [
            'Runner.php',
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Lib\Helper;
            class Runner {
                public function run(): void { Helper::doStuff(); }
            }
            PHP,
            'Vendor\Lib\Helper',
            'App\Runner',
        ];
        yield 'return type' => [
            'Builder.php',
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Lib\Product;
            class Builder {
                public function build(): Product { return new Product(); }
            }
            PHP,
            'Vendor\Lib\Product',
            'App\Builder',
        ];
        yield 'property type' => [
            'Holder.php',
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Lib\Config;
            class Holder {
                private Config $config;
            }
            PHP,
            'Vendor\Lib\Config',
            'App\Holder',
        ];
    }

    #[DataProvider('usesDetectionProvider')]
    public function testUsesDetection(string $fileName, string $code, string $targetFqcn, string $dependentFqcn): void
    {
        $this->createFile($fileName, $code);

        $result = $this->resolver->findDependents($this->tmpDir, [$targetFqcn]);

        $this->assertSame([], $result->extends);
        $this->assertSame([$targetFqcn => [$dependentFqcn]], $result->uses);
    }

    public function testUsesDoesNotIncludeTraitUseDeclarations(): void
    {
        $this->createFile('Consumer.php', <<<'PHP'
        <?php
        namespace App;
        use Vendor\Mixin\MyTrait;
        class Consumer { use MyTrait; }
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Mixin\MyTrait']);

        $this->assertSame(['Vendor\Mixin\MyTrait' => ['App\Consumer']], $result->traits);
        $this->assertSame([], $result->uses);
    }

    public function testUsesExtendsOnlyDoesNotAppearInUses(): void
    {
        $this->createFile('Child.php', <<<'PHP'
        <?php
        namespace App;
        class Child extends \Vendor\Base {}
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Base']);

        $this->assertSame(['Vendor\Base' => ['App\Child']], $result->extends);
        $this->assertSame([], $result->uses);
    }

    public function testUsesMultipleReferencesCountOnce(): void
    {
        $this->createFile('Multi.php', <<<'PHP'
        <?php
        namespace App;
        use Vendor\Lib\Foo;
        class Multi {
            private Foo $a;
            public function run(Foo $b): Foo { return new Foo(); }
        }
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\Lib\Foo']);

        $this->assertSame(['Vendor\Lib\Foo' => ['App\Multi']], $result->uses);
    }

    public function testUsesCountHelper(): void
    {
        $this->createFile('A.php', <<<'PHP'
        <?php
        namespace App;
        class A { public function f(\Vendor\X $x): \Vendor\Y {} }
        PHP);

        $result = $this->resolver->findDependents($this->tmpDir, ['Vendor\X', 'Vendor\Y']);

        $this->assertSame(2, $result->usesCount());
        $this->assertSame([
            'Vendor\X' => ['App\A'],
            'Vendor\Y' => ['App\A'],
        ], $result->uses);
    }

}
