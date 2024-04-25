<?php

namespace Oro\Rector\Signature;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use UnexpectedValueException;

/**
 * Compares already generated signature list with current class signatures and generates differences
 * @inspired https://github.com/craftcms/rector
 */
final class SignatureDiffer
{
    public function diff(array $oldSignatures): array
    {
        return [
            SignatureConfig::PROPERTY_TYPES => array_values(
                array_filter(
                    $oldSignatures[SignatureConfig::PROPERTY_TYPES],
                    fn ($info) => self::includePropertyType(...$info)
                )
            ),
            SignatureConfig::METHOD_RETURN_TYPES => array_values(
                array_filter(
                    $oldSignatures[SignatureConfig::METHOD_RETURN_TYPES],
                    fn ($info) => self::includeMethodReturnType(...$info)
                )
            ),
            SignatureConfig::METHOD_PARAM_TYPES => array_values(
                array_filter(
                    $oldSignatures[SignatureConfig::METHOD_PARAM_TYPES],
                    fn ($info) => self::includeMethodParamType(...$info)
                )
            ),
        ];
    }

    private function includePropertyType(string $className, string $propertyName, string $type): bool
    {
        if (!class_exists($className)) {
            // Just in case it was renamed
            return true;
        }
        $class = new ReflectionClass($className);
        if (!$class->hasProperty($propertyName)) {
            return true;
        }
        $oldType = $class->getProperty($propertyName)->getType();
        return $type !== $this->serializeType($oldType, $className);
    }

    private function includeMethodReturnType(string $className, string $method, string $returnType): bool
    {
        if (!class_exists($className)) {
            // Just in case it was renamed
            return true;
        }
        $class = new ReflectionClass($className);
        if (!$class->hasMethod($method)) {
            return true;
        }
        $oldReturnType = $class->getMethod($method)->getReturnType();
        return $returnType !== $this->serializeType($oldReturnType, $className);
    }

    private function includeMethodParamType(string $className, string $method, int $position, string $paramType): bool
    {
        if (!class_exists($className)) {
            // Just in case it was renamed
            return true;
        }
        $class = new ReflectionClass($className);
        if (!$class->hasMethod($method)) {
            return true;
        }
        $oldParams = $class->getMethod($method)->getParameters();
        return !isset($oldParams[$position]) || $paramType !== $this->serializeType($oldParams[$position]->getType(), $className);
    }

    private function serializeType(?ReflectionType $type, string $className): ?string
    {
        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(function (ReflectionNamedType $type) use ($className) {
                $name = $type->getName();
                return $name === 'self' ? $className : $name;
            }, $type->getTypes()));
        }

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            if ($name === 'self') {
                $name = $className;
            }
            if ($name !== 'mixed' && $type->allowsNull()) {
                return "$name|null";
            }
            return $name;
        }

        throw new UnexpectedValueException(sprintf('Unexpected reflection type: %s', get_class($type)));
    }
}
