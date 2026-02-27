<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Extractor;

final class PropertyInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $visibility,
        public readonly ?string $type,
        public readonly ?string $defaultHash,
        public readonly bool $isStatic,
        public readonly bool $isReadonly,
    ) {
    }

    public function signatureEquals(self $other): bool
    {
        return $this->visibility === $other->visibility
            && $this->normalizeType($this->type) === $this->normalizeType($other->type)
            && $this->isStatic === $other->isStatic
            && $this->isReadonly === $other->isReadonly;
    }

    public function valueEquals(self $other): bool
    {
        return $this->defaultHash === $other->defaultHash;
    }

    private function normalizeType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        return ltrim($type, '\\');
    }
}
