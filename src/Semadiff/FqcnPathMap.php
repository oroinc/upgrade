<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff;

final class FqcnPathMap
{
    /** @var array<string, string> FQCN => absolute path */
    private array $map = [];

    public function set(string $fqcn, string $path): void
    {
        $this->map[$fqcn] = $path;
    }

    public function get(string $fqcn): ?string
    {
        return $this->map[$fqcn] ?? null;
    }

    public function merge(self $other): void
    {
        foreach ($other->map as $fqcn => $path) {
            $this->map[$fqcn] = $path;
        }
    }

    public function count(): int
    {
        return count($this->map);
    }
}
