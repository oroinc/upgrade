<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Extractor;

final class ConstantInfo
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $type,
        public readonly string $valueHash,
        public readonly string $visibility,
        public readonly bool $isFinal = false,
    ) {
    }

    public function signatureEquals(self $other): bool
    {
        $normalizeType = fn (?string $tp) => $tp !== null ? ltrim($tp, '\\') : null;

        return $this->visibility === $other->visibility
            && $this->isFinal === $other->isFinal
            && $normalizeType($this->type) === $normalizeType($other->type);
    }

    public function valueEquals(self $other): bool
    {
        return $this->valueHash === $other->valueHash;
    }
}
