<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Extractor;

final class MethodInfo
{
    /**
     * @param array<int, array<string, mixed>> $params
     */
    public function __construct(
        public readonly string $name,
        public readonly string $visibility,
        public readonly bool $isStatic,
        public readonly bool $isAbstract,
        public readonly bool $isFinal,
        public readonly array $params,
        public readonly ?string $returnType,
        public readonly string $bodyHash,
    ) {
    }

    public function signatureEquals(self $other): bool
    {
        return $this->visibility === $other->visibility
            && $this->isStatic === $other->isStatic
            && $this->isAbstract === $other->isAbstract
            && $this->isFinal === $other->isFinal
            && $this->normalizedParams() === $other->normalizedParams()
            && $this->normalizedReturnType() === $other->normalizedReturnType();
    }

    public function bodyEquals(self $other): bool
    {
        return $this->bodyHash === $other->bodyHash;
    }

    /**
     * @return list<array{name: string, type: ?string, variadic: bool, byRef: bool}>
     */
    private function normalizedParams(): array
    {
        return array_values(array_map(
            /**
             * @param array<string, mixed> $param
             * @return array{name: string, type: ?string, variadic: bool, byRef: bool}
             */
            function (array $param): array {
                $name = $param['name'];
                assert(is_string($name));

                return [
                    'name' => $name,
                    'type' => $this->normalizeType(isset($param['type']) && is_string($param['type']) ? $param['type'] : null),
                    'variadic' => (bool) ($param['variadic'] ?? false),
                    'byRef' => (bool) ($param['byRef'] ?? false),
                ];
            },
            $this->params,
        ));
    }

    private function normalizedReturnType(): ?string
    {
        return $this->normalizeType($this->returnType);
    }

    private function normalizeType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        // Strip leading backslash from fully qualified names
        $type = ltrim($type, '\\');

        // Normalize union/intersection types by sorting parts
        if (str_contains($type, '|')) {
            $parts = explode('|', $type);
            $parts = array_map(fn (string $pt) => ltrim($pt, '\\'), $parts);
            sort($parts);
            return implode('|', $parts);
        }

        if (str_contains($type, '&')) {
            $parts = explode('&', $type);
            $parts = array_map(fn (string $pt) => ltrim($pt, '\\'), $parts);
            sort($parts);
            return implode('&', $parts);
        }

        return $type;
    }
}
