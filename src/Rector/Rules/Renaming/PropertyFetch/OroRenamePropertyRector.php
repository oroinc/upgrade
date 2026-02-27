<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Renaming\PropertyFetch;

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\OroRenameProperty;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Exception\ShouldNotHappenException;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use RectorPrefix202602\Webmozart\Assert\Assert;

/**
 * Modified copy of \Rector\Renaming\Rector\PropertyFetch\RenamePropertyRector, Rector v2.1.2
 *
 * Replaces defined old properties by new ones with the ability to specify target classes.
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
final class OroRenamePropertyRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $renamedProperties = [];
    private bool $hasChanged = false;

    public function getNodeTypes(): array
    {
        return [PropertyFetch::class, ClassLike::class];
    }

    public function refactor(Node $node): ?Node
    {
        $this->hasChanged = false;

        $classReflection = $this->resolveClassReflection($node);
        if (null === $classReflection) {
            return null;
        }

        foreach ($this->renamedProperties as $renamedProperty) {
            if (!$this->isApplicableRule($renamedProperty, $classReflection)) {
                continue;
            }

            if ($node instanceof ClassLike) {
                $this->processClassLike($node, $renamedProperty);
            } elseif ($node instanceof PropertyFetch) {
                $this->processPropertyFetch($node, $renamedProperty);
            }
        }

        return $this->hasChanged ? $node : null;
    }

    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, OroRenameProperty::class);
        $this->renamedProperties = $configuration;
    }

    private function resolveClassReflection(Node $node): ?ClassReflection
    {
        if (!$node->hasAttribute(AttributeKey::SCOPE)) {
            return null;
        }

        return ScopeFetcher::fetch($node)->getClassReflection();
    }

    private function isApplicableRule(OroRenameProperty $renamedProperty, ClassReflection $classReflection): bool
    {
        $applyTo = $renamedProperty->getApplyTo();

        if (empty($applyTo)) {
            throw new ShouldNotHappenException(
                sprintf('%s::applyTo property value cannot be empty', self::class)
            );
        }

        return $this->isClassInApplyToArray($classReflection, $applyTo);
    }

    private function processClassLike(ClassLike $classLike, OroRenameProperty $renameProperty): void
    {
        $classLikeName = (string) $this->getName($classLike);
        if (!$this->isTargetClass($classLikeName, $renameProperty)) {
            return;
        }

        $property = $classLike->getProperty($renameProperty->getOldProperty());
        if (!$property instanceof Property) {
            return;
        }

        if ($this->hasConflictingProperty($classLike, $renameProperty->getNewProperty())) {
            return;
        }

        $this->renamePropertyInClass($property, $renameProperty->getNewProperty());
    }

    private function processPropertyFetch(PropertyFetch $propertyFetch, OroRenameProperty $renamedProperty): void
    {
        if (!$this->isTargetPropertyFetch($propertyFetch, $renamedProperty)) {
            return;
        }

        $propertyFetch->name = new Identifier($renamedProperty->getNewProperty());
        $this->hasChanged = true;
    }

    private function isTargetClass(string $classLikeName, OroRenameProperty $renameProperty): bool
    {
        $renamePropertyObjectType = $renameProperty->getObjectType();
        $className = $renamePropertyObjectType->getClassName();

        if ($classLikeName === $className) {
            return true;
        }

        $classLikeNameObjectType = new ObjectType($classLikeName);
        $classNameObjectType = new ObjectType($className);

        return $classNameObjectType->isSuperTypeOf($classLikeNameObjectType)->yes();
    }

    private function hasConflictingProperty(ClassLike $classLike, string $newPropertyName): bool
    {
        return $classLike->getProperty($newPropertyName) instanceof Property;
    }

    private function renamePropertyInClass(Property $property, string $newPropertyName): void
    {
        $property->props[0]->name = new VarLikeIdentifier($newPropertyName);
        $this->hasChanged = true;
    }

    private function isTargetPropertyFetch(PropertyFetch $propertyFetch, OroRenameProperty $renamedProperty): bool
    {
        if (!$this->isName($propertyFetch, $renamedProperty->getOldProperty())) {
            return false;
        }

        return $this->isObjectType($propertyFetch->var, $renamedProperty->getObjectType());
    }

    /**
     * Check if class reflection matches any class in applyTo array.
     * Includes direct class name, parent classes and implemented interfaces.
     */
    private function isClassInApplyToArray(ClassReflection $classReflection, array $applyTo): bool
    {
        // Check direct class name
        if (in_array($classReflection->getName(), $applyTo, true)) {
            return true;
        }

        // Check parent classes
        foreach ($classReflection->getParents() as $parentClass) {
            if (in_array($parentClass->getName(), $applyTo, true)) {
                return true;
            }
        }

        // Check implemented interfaces
        foreach ($classReflection->getImmediateInterfaces() as $interface) {
            if (in_array($interface->getName(), $applyTo, true)) {
                return true;
            }
        }

        return false;
    }
}
