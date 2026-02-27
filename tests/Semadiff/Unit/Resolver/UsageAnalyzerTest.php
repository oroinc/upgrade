<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Resolver;

use Oro\UpgradeToolkit\Semadiff\Resolver\DependencyResolver;
use Oro\UpgradeToolkit\Semadiff\Resolver\UsageAnalyzer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UsageAnalyzerTest extends TestCase
{
    private UsageAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new UsageAnalyzer();
    }

    // ── Method overrides ────────────────────────────────────────────────

    /**
     * @return iterable<string, array{list<string>, list<string>}>
     */
    public static function methodOverrideProvider(): iterable
    {
        yield 'detects override when method changed' => [['save'], ['save']];
        yield 'no override when method not changed' => [['load'], []];
    }

    #[DataProvider('methodOverrideProvider')]
    public function testMethodOverrideDetection(array $changedMethods, array $expectedOverrides): void
    {
        $code = <<<'PHP'
        <?php
        namespace App;
        use Vendor\Base;
        class Child extends Base {
            public function save(string $name): void {}
        }
        PHP;

        $result = $this->analyzer->analyze(
            'Vendor\Base',
            $changedMethods,
            false,
            'App\Child',
            $code,
            DependencyResolver::TYPE_EXTENDS,
        );

        $this->assertSame($expectedOverrides, $result->overriddenMethods);
    }

    public function testDetectsConstructorOverride(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App;
        use Vendor\Base;
        class Child extends Base {
            public function __construct() {}
        }
        PHP;

        $result = $this->analyzer->analyze(
            'Vendor\Base',
            ['__construct'],
            true,
            'App\Child',
            $code,
            DependencyResolver::TYPE_EXTENDS,
        );

        $this->assertTrue($result->overridesConstructor);
    }

    // ── Parent calls ────────────────────────────────────────────────────

    public function testDetectsParentMethodCall(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App;
        use Vendor\Base;
        class Child extends Base {
            public function save(string $name): void {
                parent::save($name);
            }
        }
        PHP;

        $result = $this->analyzer->analyze(
            'Vendor\Base',
            ['save'],
            false,
            'App\Child',
            $code,
            DependencyResolver::TYPE_EXTENDS,
        );

        $this->assertSame(['save'], $result->parentMethodCalls);
    }

    public function testDetectsParentConstructorCall(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App;
        use Vendor\Base;
        class Child extends Base {
            public function __construct(string $name) {
                parent::__construct($name);
            }
        }
        PHP;

        $result = $this->analyzer->analyze(
            'Vendor\Base',
            ['__construct'],
            true,
            'App\Child',
            $code,
            DependencyResolver::TYPE_EXTENDS,
        );

        $this->assertTrue($result->callsConstructor);
        $this->assertContains('__construct', $result->parentMethodCalls);
    }

    // ── Static calls ────────────────────────────────────────────────────

    /**
     * @return iterable<string, array{list<string>, list<string>}>
     */
    public static function staticCallProvider(): iterable
    {
        yield 'detects static call for changed method' => [['process'], ['process']];
        yield 'no static call for different method' => [['process'], []];
    }

    #[DataProvider('staticCallProvider')]
    public function testStaticCallDetection(array $changedMethods, array $expectedStaticCalls): void
    {
        // When expecting empty result, the code calls a different method than changed
        $calledMethod = $expectedStaticCalls !== [] ? 'process' : 'other';
        $code = <<<PHP
        <?php
        namespace App;
        use Vendor\Helper;
        class Consumer {
            public function run(): void {
                Helper::{$calledMethod}('data');
            }
        }
        PHP;

        $result = $this->analyzer->analyze(
            'Vendor\Helper',
            $changedMethods,
            false,
            'App\Consumer',
            $code,
            DependencyResolver::TYPE_USES,
        );

        $this->assertSame($expectedStaticCalls, $result->staticMethodCalls);
    }

    // ── Instance calls ──────────────────────────────────────────────────

    /**
     * @return iterable<string, array{list<string>, list<string>}>
     */
    public static function instanceCallProvider(): iterable
    {
        yield 'detects instance call for changed method' => [['process'], ['process']];
        yield 'no instance call for unchanged method' => [['process'], []];
    }

    #[DataProvider('instanceCallProvider')]
    public function testInstanceCallDetection(array $changedMethods, array $expectedInstanceCalls): void
    {
        $calledMethod = $expectedInstanceCalls !== [] ? 'process' : 'other';
        $code = <<<PHP
        <?php
        namespace App;
        use Vendor\Service;
        class Consumer {
            public function run(Service \$svc): void {
                \$svc->{$calledMethod}('data');
            }
        }
        PHP;

        $result = $this->analyzer->analyze(
            'Vendor\Service',
            $changedMethods,
            false,
            'App\Consumer',
            $code,
            DependencyResolver::TYPE_USES,
        );

        $this->assertSame($expectedInstanceCalls, $result->instanceMethodCalls);
    }

    // ── new VendorFqcn() ────────────────────────────────────────────────

    /**
     * @return iterable<string, array{list<string>, bool, bool}>
     */
    public static function newCallProvider(): iterable
    {
        yield 'detects new when constructor changed' => [['__construct'], true, true];
        yield 'no new detection when constructor unchanged' => [['save'], false, false];
    }

    #[DataProvider('newCallProvider')]
    public function testNewCallDetection(
        array $changedMethods,
        bool $constructorChanged,
        bool $expectedCallsConstructor,
    ): void {
        $code = <<<'PHP'
        <?php
        namespace App;
        use Vendor\Service;
        class Consumer {
            public function create(): Service {
                return new Service('arg');
            }
        }
        PHP;

        $result = $this->analyzer->analyze(
            'Vendor\Service',
            $changedMethods,
            $constructorChanged,
            'App\Consumer',
            $code,
            DependencyResolver::TYPE_USES,
        );

        $this->assertSame($expectedCallsConstructor, $result->callsConstructor);
    }

    // ── Interface implementations ───────────────────────────────────────

    /**
     * @return iterable<string, array{string, string, string, bool, list<string>}>
     */
    public static function interfaceDetectionProvider(): iterable
    {
        yield 'detects interface implementation' => [
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Contract\ServiceInterface;
            class Impl implements ServiceInterface {
                public function execute(string $data): void {}
                public function other(): void {}
            }
            PHP,
            'Vendor\Contract\ServiceInterface',
            'App\Impl',
            true,
            ['execute'],
        ];
        yield 'interface not flagged for extends' => [
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Base;
            class Child extends Base {
                public function execute(): void {}
            }
            PHP,
            'Vendor\Base',
            'App\Child',
            false,
            [],
        ];
    }

    #[DataProvider('interfaceDetectionProvider')]
    public function testInterfaceDetection(
        string $code,
        string $vendorFqcn,
        string $dependentFqcn,
        bool $expectedImplements,
        array $expectedMethods,
    ): void {
        $type = $expectedImplements
            ? DependencyResolver::TYPE_IMPLEMENTS
            : DependencyResolver::TYPE_EXTENDS;

        $result = $this->analyzer->analyze(
            $vendorFqcn,
            ['execute'],
            false,
            $dependentFqcn,
            $code,
            $type,
        );

        $this->assertSame($expectedImplements, $result->implementsInterface);
        $this->assertSame($expectedMethods, $result->implementedMethods);
    }

    // ── Trait usage ─────────────────────────────────────────────────────

    /**
     * @return iterable<string, array{string, string, string, string, bool}>
     */
    public static function traitDetectionProvider(): iterable
    {
        yield 'detects trait usage' => [
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Mixin\MyTrait;
            class Consumer {
                use MyTrait;
            }
            PHP,
            'Vendor\Mixin\MyTrait',
            'App\Consumer',
            DependencyResolver::TYPE_TRAITS,
            true,
        ];
        yield 'trait not flagged for extends' => [
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Base;
            class Child extends Base {}
            PHP,
            'Vendor\Base',
            'App\Child',
            DependencyResolver::TYPE_EXTENDS,
            false,
        ];
    }

    #[DataProvider('traitDetectionProvider')]
    public function testTraitDetection(
        string $code,
        string $vendorFqcn,
        string $dependentFqcn,
        string $type,
        bool $expectedUsesTrait,
    ): void {
        $result = $this->analyzer->analyze(
            $vendorFqcn,
            ['helperMethod'],
            false,
            $dependentFqcn,
            $code,
            $type,
        );

        $this->assertSame($expectedUsesTrait, $result->usesTrait);
    }

    // ── hasAnyUsage gate ────────────────────────────────────────────────

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function emptyUsageProvider(): iterable
    {
        yield 'class not found' => [
            <<<'PHP'
            <?php
            namespace App;
            class OtherClass {}
            PHP,
            'App\NonExistent',
        ];
        yield 'no changed methods referenced' => [
            <<<'PHP'
            <?php
            namespace App;
            use Vendor\Base;
            class Child extends Base {
                public function unrelated(): void {}
            }
            PHP,
            'App\Child',
        ];
    }

    #[DataProvider('emptyUsageProvider')]
    public function testEmptyUsage(string $code, string $dependentFqcn): void
    {
        $result = $this->analyzer->analyze(
            'Vendor\Base',
            ['save'],
            false,
            $dependentFqcn,
            $code,
            DependencyResolver::TYPE_EXTENDS,
        );

        $this->assertFalse($result->hasAnyUsage());
    }

    // ── Multiple detections ─────────────────────────────────────────────

    public function testMultipleDetections(): void
    {
        $code = <<<'PHP'
        <?php
        namespace App;
        use Vendor\Base;
        class Child extends Base {
            public function __construct(string $name) {
                parent::__construct($name);
            }
            public function save(string $data): void {
                parent::save($data);
            }
            public function load(): void {
                $other = new Base();
            }
        }
        PHP;

        $result = $this->analyzer->analyze(
            'Vendor\Base',
            ['save', '__construct'],
            true,
            'App\Child',
            $code,
            DependencyResolver::TYPE_EXTENDS,
        );

        $this->assertTrue($result->overridesConstructor);
        $this->assertTrue($result->callsConstructor);
        $this->assertSame(['save'], $result->overriddenMethods);
        $this->assertContains('save', $result->parentMethodCalls);
        $this->assertContains('__construct', $result->parentMethodCalls);
        $this->assertTrue($result->hasAnyUsage());
    }

    public function testInvalidCodeReturnsEmptyUsage(): void
    {
        $result = $this->analyzer->analyze(
            'Vendor\Base',
            ['save'],
            false,
            'App\Broken',
            '<?php this is not valid {{{{',
            DependencyResolver::TYPE_EXTENDS,
        );

        $this->assertFalse($result->hasAnyUsage());
    }
}
