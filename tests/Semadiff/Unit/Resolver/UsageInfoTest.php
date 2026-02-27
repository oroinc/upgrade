<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Unit\Resolver;

use Oro\UpgradeToolkit\Semadiff\Resolver\UsageInfo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UsageInfoTest extends TestCase
{
    public function testHasAnyUsageReturnsFalseWhenEmpty(): void
    {
        $info = new UsageInfo(
            dependentFqcn: 'App\Foo',
            overridesConstructor: false,
            callsConstructor: false,
            overriddenMethods: [],
            parentMethodCalls: [],
            instanceMethodCalls: [],
            staticMethodCalls: [],
            implementsInterface: false,
            implementedMethods: [],
            usesTrait: false,
        );
        $this->assertFalse($info->hasAnyUsage());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function hasAnyUsageTrueProvider(): iterable
    {
        yield 'overridden methods' => [['overriddenMethods' => ['save']]];
        yield 'overrides constructor' => [['overridesConstructor' => true]];
        yield 'calls constructor' => [['callsConstructor' => true]];
        yield 'implements interface' => [['implementsInterface' => true, 'implementedMethods' => ['execute']]];
        yield 'uses trait' => [['usesTrait' => true]];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('hasAnyUsageTrueProvider')]
    public function testHasAnyUsageReturnsTrueForSingleFlag(array $overrides): void
    {
        $defaults = [
            'dependentFqcn' => 'App\Foo',
            'overridesConstructor' => false,
            'callsConstructor' => false,
            'overriddenMethods' => [],
            'parentMethodCalls' => [],
            'instanceMethodCalls' => [],
            'staticMethodCalls' => [],
            'implementsInterface' => false,
            'implementedMethods' => [],
            'usesTrait' => false,
        ];
        $args = array_merge($defaults, $overrides);

        $info = new UsageInfo(...$args);
        $this->assertTrue($info->hasAnyUsage());
    }

    public function testToArrayAndFromArrayRoundtrip(): void
    {
        $info = new UsageInfo(
            dependentFqcn: 'App\Child',
            overridesConstructor: true,
            callsConstructor: false,
            overriddenMethods: ['save', 'load'],
            parentMethodCalls: ['save'],
            instanceMethodCalls: ['process'],
            staticMethodCalls: ['create'],
            implementsInterface: false,
            implementedMethods: [],
            usesTrait: true,
        );

        $array = $info->toArray();
        $restored = UsageInfo::fromArray($array);

        $this->assertSame($info->dependentFqcn, $restored->dependentFqcn);
        $this->assertSame($info->overridesConstructor, $restored->overridesConstructor);
        $this->assertSame($info->callsConstructor, $restored->callsConstructor);
        $this->assertSame($info->overriddenMethods, $restored->overriddenMethods);
        $this->assertSame($info->parentMethodCalls, $restored->parentMethodCalls);
        $this->assertSame($info->instanceMethodCalls, $restored->instanceMethodCalls);
        $this->assertSame($info->staticMethodCalls, $restored->staticMethodCalls);
        $this->assertSame($info->implementsInterface, $restored->implementsInterface);
        $this->assertSame($info->implementedMethods, $restored->implementedMethods);
        $this->assertSame($info->usesTrait, $restored->usesTrait);
    }

    public function testFromArrayWithJsonDecodedData(): void
    {
        $json = '{"dependentFqcn":"App\\\\Foo","overridesConstructor":true,"callsConstructor":false,"overriddenMethods":["save"],"parentMethodCalls":[],"instanceMethodCalls":[],"staticMethodCalls":[],"implementsInterface":false,"implementedMethods":[],"usesTrait":false}';
        $data = json_decode($json, true);
        assert(is_array($data));

        $info = UsageInfo::fromArray($data);
        $this->assertSame('App\Foo', $info->dependentFqcn);
        $this->assertTrue($info->overridesConstructor);
        $this->assertSame(['save'], $info->overriddenMethods);
    }
}
