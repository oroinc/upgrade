<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\ParamConverter;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Rector\Symfony\Enum\SensioAttribute;
use Rector\Symfony\Enum\SymfonyAnnotation;
use Rector\ValueObject\PhpVersionFeature;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use ReflectionClass;

/**
 * Modified copy of \Rector\Symfony\Symfony62\Rector\ClassMethod\ParamConverterAttributeToMapEntityAttributeRector, Rector v2.1.2
 *
 * Replace the addressValidationAction method`s ParamConverter
 * attributes of the Oro\Bundle\AddressValidationBundle\Controller\Frontend\AbstractAddressValidationController
 * and Oro\Bundle\AddressValidationBundle\Controller\AbstractAddressValidationController classes'
 * children with the MapEntity attribute
 *
 * Copyright (c) 2017-present Tomáš Votruba (https://tomasvotruba.cz)
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class AddressValidationActionParamConverterAttributeToMapEntityAttributeRector extends AbstractRector implements MinPhpVersionInterface
{
    private const TARGET_CLASSES = [
        'Oro\Bundle\AddressValidationBundle\Controller\Frontend\AbstractAddressValidationController',
        'Oro\Bundle\AddressValidationBundle\Controller\AbstractAddressValidationController',
    ];

    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::ATTRIBUTES;
    }

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$this->isApplicable($node)) {
            return null;
        }

        $hasChanged = false;
        foreach ($node->attrGroups as $attrGroupKey => $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (!$this->isNames($attr, [SensioAttribute::PARAM_CONVERTER, SensioAttribute::ENTITY])) {
                    continue;
                }
                $attribute = $this->refactorAttribute($node, $attr, $attrGroup);
                if ($attribute instanceof Attribute) {
                    unset($node->attrGroups[$attrGroupKey]);
                    $hasChanged = true;
                }
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }

    private function isApplicable(Node $node): bool
    {
        return $node->isPublic()
            && $this->isName($node, 'addressValidationAction')
            && $this->isAddressValidationControllerChild($node);
    }

    private function isAddressValidationControllerChild(Node $classMethod): bool
    {
        try {
            $scope = ScopeFetcher::fetch($classMethod);
            $classReflection = $scope->getClassReflection();
            $parentClassName = $classReflection->getParentClass()?->getName();
        } catch (\Throwable $e) {
            return false;
        }

        if (!$parentClassName) {
            return false;
        }

        return in_array($parentClassName, self::TARGET_CLASSES, true);
    }

    private function refactorAttribute(ClassMethod $classMethod, Attribute $attribute, AttributeGroup $attributeGroup): ?Attribute
    {
        $firstArg = $attribute->args[0] ?? null;
        if (!$firstArg instanceof Arg) {
            return null;
        }
        if (!$firstArg->value instanceof String_) {
            return null;
        }
        $optionsIndex = $this->getIndexForOptionsArg($attribute->args);
        $exprIndex = $this->getIndexForExprArg($attribute->args);
        if (!$optionsIndex && !$exprIndex) {
            return null;
        }

        $name = $firstArg->value->value;
        $this->addClassMethodParam($classMethod, $attribute, $name);
        $newArguments = $this->getNewArgs($attribute);

        $attribute->args = $newArguments;
        $attribute->name = new FullyQualified(SymfonyAnnotation::MAP_ENTITY);

        $this->addMapEntityAttribute($classMethod, $name, $attributeGroup);
        return $attribute;
    }

    private function addClassMethodParam(ClassMethod $classMethod, Attribute $attribute, string $name): void
    {
        $classAgrIndex = $this->getIndexForClassArg($attribute->args);
        if (!$classAgrIndex) {
            return;
        }

        $className = $attribute->args[$classAgrIndex];
        if ($className->value instanceof Expr\ClassConstFetch) {
            $className =  $className->value->class->name;
        }

        $hasNeededParameter = false;
        /** @var Param $param */
        foreach ($classMethod->params as $param) {
            $paramName = $param->var->name;

            if ($param->type instanceof NullableType) {
                $paramType = $param->type->type->name;
            } elseif ($param->type instanceof UnionType) {
                $paramType = $param->type;
            } else {
                $paramType = $param->type->name;
            }

            if ($name === $paramName && $className === $paramType) {
                $hasNeededParameter = true;
            }
        }

        if ($hasNeededParameter) {
            return;
        }

        $classMethod->params[] = new Param(
            new Variable($name),
            default: new ConstFetch(new Name('null')),
            type: new UnionType([
                new FullyQualified($className),
                new Identifier('null')
            ]),
        );
    }

    private function getIndexForClassArg(array $args): ?int
    {
        foreach ($args as $key => $arg) {
            if ($arg->name instanceof Identifier && 'class' === $arg->name->name) {
                return $key;
            }
        }
        return null;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getNewArgs(Attribute $attribute): array
    {
        $classArgIndex = $this->getIndexForClassArg($attribute->args);
        if (!$classArgIndex) {
            return [];
        }
        $className = $attribute->args[$classArgIndex];
        if ($className->value instanceof Expr\ClassConstFetch) {
            $className = $className->value->class->name;
        }

        if (!$this->isEntity($className)) {
            return [];
        }

        $newArgs = [];
        // Handle expr parameter
        $exprIndex = $this->getIndexForExprArg($attribute->args);
        if ($exprIndex) {
            $exprArg = $attribute->args[$exprIndex];
            if ($exprArg instanceof Arg) {
                $newArgs[] = new Arg($exprArg->value, false, false, [], new Identifier('expr'));
            }
        }

        // Handle options parameter
        $optionsIndex = $this->getIndexForOptionsArg($attribute->args);
        if ($optionsIndex) {
            $options = $attribute->args[$optionsIndex];
            if ($options->value instanceof Array_) {
                /** @var ArrayItem $arrayItem */
                foreach ($options->value->items as $arrayItem) {
                    if (!$arrayItem instanceof ArrayItem || !$arrayItem->key instanceof String_) {
                        continue;
                    }

                    $newArgs[] = new Arg($arrayItem->value, false, false, [], new Identifier($arrayItem->key->value));
                }
            }
        }

        return $newArgs;
    }

    private function isEntity(string $className): bool
    {
        try {
            $targetClass = new ReflectionClass($className);
            foreach ($targetClass->getAttributes() as $attr) {
                if ('Doctrine\ORM\Mapping\Entity' === $attr->getName()) {
                    return true;
                }
            }
        } catch (\ReflectionException $e) {
            // Fallback to string check if reflection fails
        }

        return str_contains($className, '\\Entity\\');
    }

    private function addMapEntityAttribute(ClassMethod $classMethod, string $variableName, AttributeGroup $attributeGroup): void
    {
        foreach ($classMethod->params as $param) {
            if (!$param->var instanceof Variable) {
                continue;
            }
            if (!$this->isName($param->var, $variableName)) {
                continue;
            }
            $param->attrGroups = [$attributeGroup];
        }
    }

    private function getIndexForOptionsArg(array $args): ?int
    {
        foreach ($args as $key => $arg) {
            if ($arg->name instanceof Identifier && 'options' === $arg->name->name) {
                return $key;
            }
        }
        return null;
    }

    private function getIndexForExprArg(array $args): ?int
    {
        foreach ($args as $key => $arg) {
            if ($arg->name instanceof Identifier && 'expr' === $arg->name->name) {
                return $key;
            }
        }
        return null;
    }
}
