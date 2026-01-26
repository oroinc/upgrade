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
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Rector\AbstractRector;
use Rector\Symfony\Enum\SensioAttribute;
use Rector\Symfony\Enum\SymfonyAnnotation;
use Rector\ValueObject\PhpVersionFeature;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;

/**
 * Modified copy of \Rector\Symfony\Symfony62\Rector\ClassMethod\ParamConverterAttributeToMapEntityAttributeRector, Rector v2.1.2
 *
 * Replace ParamConverter attribute with mappings with the MapEntity attribute
 * Improved MapEntity attribute arguments processing
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
 */
final class OroParamConverterAttributeToMapEntityAttributeRector extends AbstractRector implements MinPhpVersionInterface
{
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
        if (!$node->isPublic()) {
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

    private function refactorAttribute(ClassMethod $classMethod, Attribute $attribute, AttributeGroup $attributeGroup): ?Attribute
    {
        $firstArg = $attribute->args[0] ?? null;
        if (!$firstArg instanceof Arg || !$firstArg->value instanceof String_) {
            return null;
        }

        $optionsIndex = $this->getIndexForOptionsArg($attribute->args);
        $parameterName = $firstArg->value->value;
        $mappingArg = $attribute->args[$optionsIndex] ?? null;
        $mappingExpr = $mappingArg instanceof Arg ? $mappingArg->value : null;

        $newArguments = $this->getNewArguments($mappingExpr);

        // Remove original arguments
        unset($attribute->args[0]);
        if ($optionsIndex !== null) {
            unset($attribute->args[$optionsIndex]);
        }

        $attribute->args = $newArguments;
        $attribute->name = new FullyQualified(SymfonyAnnotation::MAP_ENTITY);
        $this->addMapEntityAttribute($classMethod, $parameterName, $attributeGroup);

        return $attribute;
    }

    private function getNewArguments(?Expr $mapping): array
    {
        if (!$mapping instanceof Array_) {
            return [];
        }

        $newArguments = [];
        $repositoryMethodName = null;
        $mappingArguments = [];

        foreach ($mapping->items as $item) {
            $result = $this->processArrayItem($item, $mappingArguments);
            if ($result === null) {
                continue;
            }

            if ($result['type'] === 'repository_method') {
                $repositoryMethodName = $result['value'];
            } elseif ($result['type'] === 'argument') {
                $newArguments[] = $result['value'];
            }
        }

        if ($repositoryMethodName !== null) {
            $newArguments[] = $this->createExprArgument($repositoryMethodName, $mappingArguments);
        }

        return $newArguments;
    }

    private function processArrayItem(?ArrayItem $item, array &$mappingArguments): ?array
    {
        if (!$item instanceof ArrayItem || !$item->key instanceof String_) {
            return null;
        }

        $optionKey = $item->key->value;

        if ($this->shouldSkipOption($optionKey)) {
            return $optionKey === 'repository_method' && $item->value instanceof String_
                ? ['type' => 'repository_method', 'value' => $item->value->value]
                : null;
        }

        if ($optionKey === 'mapping' && $item->value instanceof Array_) {
            $mappingArguments = $this->extractMappingArguments($item->value);
        }

        return [
            'type' => 'argument',
            'value' => new Arg($item->value, false, false, [], new Identifier($optionKey))
        ];
    }

    private function shouldSkipOption(string $optionKey): bool
    {
        return in_array($optionKey, ['repository_method', 'map_method_signature', 'entity_manager'], true);
    }

    private function extractMappingArguments(Array_ $mappingArray): array
    {
        $mappingArguments = [];
        foreach ($mappingArray->items as $mappingItem) {
            if ($mappingItem instanceof ArrayItem && $mappingItem->value instanceof String_) {
                $mappingArguments[] = $mappingItem->value->value;
            }
        }

        return $mappingArguments;
    }

    private function createExprArgument(string $repositoryMethodName, array $mappingArguments): Arg
    {
        $methodArguments = !empty($mappingArguments) ? implode(', ', $mappingArguments) : 'id';
        $exprString = sprintf('repository.%s(%s)', $repositoryMethodName, $methodArguments);

        return new Arg(new String_($exprString), false, false, [], new Identifier('expr'));
    }

    private function addMapEntityAttribute(ClassMethod $classMethod, string $variableName, AttributeGroup $attributeGroup): void
    {
        foreach ($classMethod->params as $param) {
            if ($param->var instanceof Variable && $this->isName($param->var, $variableName)) {
                $param->attrGroups = [$attributeGroup];
                break;
            }
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
}
