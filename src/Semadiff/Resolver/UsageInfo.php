<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Resolver;

final class UsageInfo
{
    /**
     * @param string[] $overriddenMethods
     * @param string[] $parentMethodCalls
     * @param string[] $instanceMethodCalls
     * @param string[] $staticMethodCalls
     * @param string[] $implementedMethods
     */
    public function __construct(
        public readonly string $dependentFqcn,
        public readonly bool $overridesConstructor,
        public readonly bool $callsConstructor,
        public readonly array $overriddenMethods,
        public readonly array $parentMethodCalls,
        public readonly array $instanceMethodCalls,
        public readonly array $staticMethodCalls,
        public readonly bool $implementsInterface,
        public readonly array $implementedMethods,
        public readonly bool $usesTrait,
    ) {
    }

    public function hasAnyUsage(): bool
    {
        return $this->overridesConstructor
            || $this->callsConstructor
            || $this->overriddenMethods !== []
            || $this->parentMethodCalls !== []
            || $this->instanceMethodCalls !== []
            || $this->staticMethodCalls !== []
            || $this->implementsInterface
            || $this->usesTrait;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dependentFqcn' => $this->dependentFqcn,
            'overridesConstructor' => $this->overridesConstructor,
            'callsConstructor' => $this->callsConstructor,
            'overriddenMethods' => $this->overriddenMethods,
            'parentMethodCalls' => $this->parentMethodCalls,
            'instanceMethodCalls' => $this->instanceMethodCalls,
            'staticMethodCalls' => $this->staticMethodCalls,
            'implementsInterface' => $this->implementsInterface,
            'implementedMethods' => $this->implementedMethods,
            'usesTrait' => $this->usesTrait,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var string $dependentFqcn */
        $dependentFqcn = $data['dependentFqcn'];
        /** @var bool $overridesConstructor */
        $overridesConstructor = $data['overridesConstructor'];
        /** @var bool $callsConstructor */
        $callsConstructor = $data['callsConstructor'];
        /** @var string[] $overriddenMethods */
        $overriddenMethods = $data['overriddenMethods'];
        /** @var string[] $parentMethodCalls */
        $parentMethodCalls = $data['parentMethodCalls'];
        /** @var string[] $instanceMethodCalls */
        $instanceMethodCalls = $data['instanceMethodCalls'];
        /** @var string[] $staticMethodCalls */
        $staticMethodCalls = $data['staticMethodCalls'];
        /** @var bool $implementsInterface */
        $implementsInterface = $data['implementsInterface'];
        /** @var string[] $implementedMethods */
        $implementedMethods = $data['implementedMethods'];
        /** @var bool $usesTrait */
        $usesTrait = $data['usesTrait'];

        return new self(
            dependentFqcn: $dependentFqcn,
            overridesConstructor: $overridesConstructor,
            callsConstructor: $callsConstructor,
            overriddenMethods: $overriddenMethods,
            parentMethodCalls: $parentMethodCalls,
            instanceMethodCalls: $instanceMethodCalls,
            staticMethodCalls: $staticMethodCalls,
            implementsInterface: $implementsInterface,
            implementedMethods: $implementedMethods,
            usesTrait: $usesTrait,
        );
    }
}
