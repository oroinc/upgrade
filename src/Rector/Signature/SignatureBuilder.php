<?php

namespace Oro\UpgradeToolkit\Rector\Signature;

use Oro\UpgradeToolkit\Configuration\SignatureConfig;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\Reflection\ReflectionProvider;

/**
 * Builds signature listing array from provided array of classes
 * @inspired https://github.com/craftcms/rector
 */
class SignatureBuilder
{
    private array $signatures;
    private ReflectionProvider $reflectionProvider;

    public function __construct()
    {
        $this->signatures = [
            SignatureConfig::PROPERTY_TYPES => [],
            SignatureConfig::METHOD_RETURN_TYPES => [],
            SignatureConfig::METHOD_PARAM_TYPES => [],
        ];

        $containerFactory = new ContainerFactory('');
        $tmpDir = sys_get_temp_dir();
        $container = $containerFactory->create($tmpDir, [], []);

        $this->reflectionProvider = $container->getByType(ReflectionProvider::class);
    }

    public function build(array $classes): array
    {
        asort($classes);
        foreach ($classes as $class) {
            if ($this->reflectionProvider->hasClass($class)) {
                $class = $this->reflectionProvider->getClass($class)->getNativeReflection();
                $this->analyzeClass($class);
            }
        }

        return $this->signatures;
    }

    private function analyzeClass(\ReflectionClass $class): void
    {
        if ($class->isFinal()) {
            echo sprintf('Skipping %s%s', $class->name, PHP_EOL);
            return;
        }

        echo sprintf('Analyzing %s … ', $class->name);
        if (!$class->isInterface()) {
            $this->analyzeProperties($class);
        }
        $this->analyzeMethods($class, $class->isInterface());
        echo "✓\n";
    }

    private function analyzeProperties(\ReflectionClass $class): void
    {
        $parentClass = $class->getParentClass() ?: null;
        $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
        usort($properties, fn (\ReflectionProperty $a, \ReflectionProperty$b) => $a->getName() <=> $b->getName());

        foreach ($properties as $property) {
            $declaringClass = $property->getDeclaringClass();
            if ($declaringClass->name !== $class->name) {
                continue;
            }

            $type = $this->serializeType($property->getType(), $class->name);
            if ($type) {
                $parentHasProperty = $parentClass?->hasProperty($property->name);
                $parentProperty = $parentHasProperty ? $parentClass->getProperty($property->name) : null;

                if (!$parentHasProperty || $type !== $this->serializeType($parentProperty->getType(), $parentClass->name)) {
                    $this->signatures[SignatureConfig::PROPERTY_TYPES][] = [$class->name, $property->name, $type];
                }
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    /**
     * For interfaces, only record return types (covariant — safe to add to implementing classes).
     * Skip param types for interfaces because adding param types to a child class when an
     * intermediate vendor parent doesn't have them violates contravariance and causes fatal errors.
     */
    private function analyzeMethods(\ReflectionClass $class, bool $returnTypesOnly = false): void
    {
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);
        usort($methods, fn (\ReflectionMethod $a, \ReflectionMethod $b) => $a->getName() <=> $b->getName());

        foreach ($methods as $method) {
            if ($method->name === '__construct' || $method->getDeclaringClass()->name !== $class->name) {
                continue;
            }

            $returnType = $this->serializeType($method->getReturnType(), $class->name);
            if ($returnType) {
                $this->signatures[SignatureConfig::METHOD_RETURN_TYPES][] = [$class->name, $method->name, $returnType];
            }

            if ($returnTypesOnly) {
                continue;
            }

            foreach ($method->getParameters() as $pos => $parameter) {
                $type = $this->serializeType($parameter->getType(), $class->name);
                if ($type) {
                    $this->signatures[SignatureConfig::METHOD_PARAM_TYPES][] = [$class->name, $method->name, $pos, $type];
                }
            }
        }
    }

    private function serializeType(?\ReflectionType $type, string $className): ?string
    {
        if (null === $type) {
            return null;
        }

        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(function (\ReflectionNamedType $type) use ($className) {
                $name = $type->getName();
                return $name === 'self' ? $className : $name;
            }, $type->getTypes()));
        }

        if ($type instanceof \ReflectionNamedType) {
            $name = $type->getName();
            if ($name === 'self') {
                $name = $className;
            }
            if ($name !== 'mixed' && $type->allowsNull()) {
                return "$name|null";
            }
            return $name;
        }

        throw new \UnexpectedValueException(sprintf('Unexpected reflection type: %s', get_class($type)));
    }
}
