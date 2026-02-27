<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Extractor;

final class ClassInfo
{
    /**
     * @param MethodInfo[] $methods
     * @param PropertyInfo[] $properties
     * @param ConstantInfo[] $constants
     * @param string[] $implements
     * @param string[] $traits
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $isFinal,
        public readonly ?string $extends,
        public readonly array $implements,
        public readonly array $traits,
        public readonly array $methods,
        public readonly array $properties,
        public readonly array $constants,
    ) {
    }

    public function getMethod(string $name): ?MethodInfo
    {
        foreach ($this->methods as $method) {
            if ($method->name === $name) {
                return $method;
            }
        }

        return null;
    }

    public function getProperty(string $name): ?PropertyInfo
    {
        foreach ($this->properties as $prop) {
            if ($prop->name === $name) {
                return $prop;
            }
        }

        return null;
    }

    public function getConstant(string $name): ?ConstantInfo
    {
        foreach ($this->constants as $const) {
            if ($const->name === $name) {
                return $const;
            }
        }

        return null;
    }

    public function structureEquals(self $other): bool
    {
        $normalizeExtends = fn (?string $ext) => $ext !== null ? ltrim($ext, '\\') : null;
        /** @param string[] $list @return string[] */
        $normalizeList = function (array $list): array {
            $list = array_map(fn (string $str) => ltrim($str, '\\'), $list);
            sort($list);
            return $list;
        };

        return $normalizeExtends($this->extends) === $normalizeExtends($other->extends)
            && $normalizeList($this->implements) === $normalizeList($other->implements)
            && $normalizeList($this->traits) === $normalizeList($other->traits);
    }
}
