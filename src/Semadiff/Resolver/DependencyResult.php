<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Resolver;

final class DependencyResult
{
    /**
     * @param array<string, string[]> $extends     target FQCN → FQCNs of classes/interfaces that extend it
     * @param array<string, string[]> $implements  target FQCN → FQCNs of classes/enums that implement it
     * @param array<string, string[]> $traits      target FQCN → FQCNs of classes that use it as a trait
     * @param array<string, string[]> $uses        target FQCN → FQCNs of classes that reference it (type hints, new, instanceof, static calls, etc.)
     */
    public function __construct(
        public readonly array $extends,
        public readonly array $implements,
        public readonly array $traits,
        public readonly array $uses,
    ) {
    }

    public function merge(DependencyResult $other): self
    {
        return new self(
            $this->mergeGrouped($this->extends, $other->extends),
            $this->mergeGrouped($this->implements, $other->implements),
            $this->mergeGrouped($this->traits, $other->traits),
            $this->mergeGrouped($this->uses, $other->uses),
        );
    }

    public function extendsCount(): int
    {
        return $this->countValues($this->extends);
    }

    public function implementsCount(): int
    {
        return $this->countValues($this->implements);
    }

    public function traitsCount(): int
    {
        return $this->countValues($this->traits);
    }

    public function usesCount(): int
    {
        return $this->countValues($this->uses);
    }

    /**
     * @param array<string, string[]> $map
     */
    private function countValues(array $map): int
    {
        $count = 0;
        foreach ($map as $values) {
            $count += count($values);
        }
        return $count;
    }

    /**
     * @param array<string, string[]> $base
     * @param array<string, string[]> $additions
     * @return array<string, string[]>
     */
    private function mergeGrouped(array $base, array $additions): array
    {
        $result = $base;
        foreach ($additions as $key => $values) {
            $existing = $result[$key] ?? [];
            $result[$key] = array_values(array_unique(array_merge($existing, $values)));
        }

        return $result;
    }
}
