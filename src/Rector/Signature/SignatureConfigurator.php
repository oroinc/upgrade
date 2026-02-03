<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Signature;

use Oro\UpgradeToolkit\Configuration\SignatureConfig;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VoidType;
use Rector\Config\RectorConfig;
use Rector\Console\Style\RectorStyle;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\ValueObject\AddParamTypeDeclaration;
use Rector\TypeDeclaration\ValueObject\AddPropertyTypeDeclaration;
use Rector\TypeDeclaration\ValueObject\AddReturnTypeDeclaration;
use ReflectionClass;
use ReflectionProperty;

/**
 * Provides "on flight" rector rules to update signatures according to the generated listing
 * @inspired https://github.com/craftcms/rector
 */
final class SignatureConfigurator
{
    private static array $types = [];

    public static function configure(RectorConfig $rectorConfig): void
    {
        $filePath = sys_get_temp_dir() . '/' . SignatureConfig::FILE_NAME;
        if (!is_file($filePath)) {
            $rectorStyle = $rectorConfig->get(RectorStyle::class);
            $rectorStyle->warning(sprintf('SignatureConfigurator: Signatures list is not detected%s', PHP_EOL));

            return;
        }
        $signatures = require $filePath;

        if (array_key_exists(SignatureConfig::PROPERTY_TYPES, $signatures)) {
            $propertyTypeConfigs = [];
            foreach ($signatures[SignatureConfig::PROPERTY_TYPES] as [$className, $propertyName, $type]) {
                $propertyTypeConfigs[] = new AddPropertyTypeDeclaration($className, $propertyName, self::type($type));
            }

            $rectorConfig->ruleWithConfiguration(AddPropertyTypeDeclarationRector::class, $propertyTypeConfigs);
        }

        if (array_key_exists(SignatureConfig::METHOD_RETURN_TYPES, $signatures)) {
            $methodReturnTypeConfigs = [];
            foreach ($signatures[SignatureConfig::METHOD_RETURN_TYPES] as [$className, $method, $returnType]) {
                $methodReturnTypeConfigs[] = new AddReturnTypeDeclaration($className, $method, self::type($returnType));
            }

            $rectorConfig->ruleWithConfiguration(AddReturnTypeDeclarationRector::class, $methodReturnTypeConfigs);
        }

        if (array_key_exists(SignatureConfig::METHOD_PARAM_TYPES, $signatures)) {
            $methodParamTypeConfigs = [];
            foreach ($signatures[SignatureConfig::METHOD_PARAM_TYPES] as [$className, $method, $position, $paramType]) {
                $methodParamTypeConfigs[] = new AddParamTypeDeclaration($className, $method, $position, self::type($paramType));
            }

            $rectorConfig->ruleWithConfiguration(AddParamTypeDeclarationRector::class, $methodParamTypeConfigs);
        }
    }

    private static function type(string $type): Type
    {
        if (!isset(self::$types[$type])) {
            self::$types[$type] = self::createType($type);
        }

        return self::$types[$type];
    }

    private static function createType(string $type): Type
    {
        if (str_contains($type, '|')) {
            return self::createUnionType(explode('|', $type));
        }

        return match ($type) {
            'array' => new ArrayType(new MixedType(), new MixedType()),
            'bool' => new BooleanType(),
            'callable' => new CallableType(),
            'false' => new ConstantBooleanType(false),
            'float' => new FloatType(),
            'int' => new IntegerType(),
            'iterable' => new IterableType(new MixedType(), new MixedType()),
            'mixed' => new MixedType(true),
            'null' => new NullType(),
            'object' => new ObjectWithoutClassType(),
            'string' => new StringType(),
            'void' => new VoidType(),
            default => new ObjectType($type),
        };
    }

    private static function createUnionType(array $types): UnionType
    {
        $normalizedTypes = array_map(static fn (string $type): \PHPStan\Type\Type => self::type($type), $types);

        if (count($types) === 2 && in_array('null', $types, true)) {
            return new UnionType($normalizedTypes);
        }

        // we can't simply return `new UnionType([...])` here because UnionType ony supports nullable types currently
        // copied from https://github.com/rectorphp/rector-symfony/blob/91fd3f3882171c6f0c7e60c44e689e8d7d8ad0a4/config/sets/symfony/symfony6/symfony-return-types.php#L56-L63
        $unionTypeReflectionClass = new ReflectionClass(UnionType::class);

        /** @var UnionType $type */
        $type = $unionTypeReflectionClass->newInstanceWithoutConstructor();

        // write private property
        $typesReflectionProperty = new ReflectionProperty($type, 'types');
        $typesReflectionProperty->setValue($type, $normalizedTypes);

        return $type;
    }
}
