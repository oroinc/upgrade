<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Renaming\Name;

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\RenameClass;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitor;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Exception\ShouldNotHappenException;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Rector\Renaming\NodeManipulator\ClassRenamer;
use RectorPrefix202602\Webmozart\Assert\Assert;

/**
 * Modified copy of \Rector\Renaming\Rector\Name\RenameClassRector, Rector v2.1.2
 *
 * Replaces defined classes by new ones with the ability to specify target classes.
 * Added the applyTo property to handle the list of classes to apply the rule to.
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
final class OroRenameClassRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $applyTo = [];
    private array $config = [];
    private bool $hasChanged = false;

    public function __construct(
        private readonly ClassRenamer $classRenamer,
        private readonly ReflectionProvider $reflectionProvider
    ) {
    }

    public function getNodeTypes(): array
    {
        return [
            ClassConstFetch::class,
            // place FullyQualified before Name on purpose executed early before the Name as parent
            FullyQualified::class,
            // Name as parent of FullyQualified executed later for fallback annotation to attribute rename to Name
            Name::class,
            Property::class,
            FunctionLike::class,
            Expression::class,
            ClassLike::class,
            If_::class,
        ];
    }

    public function refactor(Node $node): Node|int|null
    {
        $this->hasChanged = false;

        foreach ($this->config as $renameClass) {
            $this->applyTo = $renameClass->getApplyTo();

            if (empty($this->applyTo)) {
                throw new ShouldNotHappenException(\sprintf('%s::applyTo property value cannot be empty', $this::class));
            }

            $classReflection = $this->resolveClassReflection($node);
            if (null === $classReflection) {
                continue;
            }

            if (!$this->isClassInApplyToArray($classReflection)) {
                continue;
            }

            $oldToNewClasses = $renameClass->getConfiguration();
            if ($oldToNewClasses === []) {
                continue;
            }

            if ($node instanceof ClassConstFetch) {
                $this->processClassConstFetch($node, $oldToNewClasses);
                $this->hasChanged = true;
            }

            $scope = $node->getAttribute(AttributeKey::SCOPE);
            $renamedNode = $this->classRenamer->renameNode($node, $oldToNewClasses, $scope);
            if ($renamedNode instanceof Node) {
                $node = $renamedNode;
                $this->hasChanged = true;
            }
        }

        return $this->hasChanged ? $node : null;
    }

    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, RenameClass::class);
        $this->config = $configuration;
    }

    /**
     * Check if class reflection matches any class in applyTo array
     * Includes direct class name, parent classes and implemented interfaces
     */
    private function isClassInApplyToArray(ClassReflection $classReflection): bool
    {
        // Check direct class name
        if (\in_array($classReflection->getName(), $this->applyTo, true)) {
            return true;
        }

        // Check parent classes
        foreach ($classReflection->getParents() as $parentClass) {
            if (\in_array($parentClass->getName(), $this->applyTo, true)) {
                return true;
            }
        }

        // Check implemented interfaces
        foreach ($classReflection->getImmediateInterfaces() as $interface) {
            if (\in_array($interface->getName(), $this->applyTo, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process ClassConstFetch node and handle interface constant validation
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processClassConstFetch(ClassConstFetch $classConstFetch, array $oldToNewClasses): ?int
    {
        if (!$classConstFetch->class instanceof FullyQualified
            || !$classConstFetch->name instanceof Identifier
            || !$this->reflectionProvider->hasClass($classConstFetch->class->toString())
        ) {
            return null;
        }

        foreach ($oldToNewClasses as $oldClass => $newClass) {
            if (!$this->isName($classConstFetch->class, $oldClass)) {
                continue;
            }

            if (!$this->reflectionProvider->hasClass($newClass)) {
                continue;
            }

            $classReflection = $this->reflectionProvider->getClass($newClass);
            if (!$classReflection->isInterface()) {
                continue;
            }

            $oldClassReflection = $this->reflectionProvider->getClass($oldClass);
            $constantName = $classConstFetch->name->toString();

            if ($oldClassReflection->hasConstant($constantName)
                && !$classReflection->hasConstant($constantName)
            ) {
                // no constant found on new interface? skip node below ClassConstFetch on this rule
                return NodeVisitor::DONT_TRAVERSE_CHILDREN;
            }
        }

        return null;
    }

    /**
     * Get ClassReflection from current file path by parsing namespace and class name
     */
    private function getClassReflectionFromFilePath(): ?ClassReflection
    {
        $filePath = $this->file->getFilePath();
        $content = \file_get_contents($filePath);

        if (false === $content) {
            return null;
        }

        $fullClassName = $this->extractFullClassName($content);
        if (null === $fullClassName) {
            return null;
        }

        if ($this->reflectionProvider->hasClass($fullClassName)) {
            return $this->reflectionProvider->getClass($fullClassName);
        }

        return null;
    }

    /**
     * Extract full class name from file content including namespace
     */
    private function extractFullClassName(string $content): ?string
    {
        $namespace = $this->extractNamespace($content);
        $className = $this->extractClassName($content);

        if (null === $className) {
            return null;
        }

        return $namespace ? $namespace . '\\' . $className : $className;
    }

    /**
     * Extract namespace from file content
     */
    private function extractNamespace(string $content): ?string
    {
        if (\preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return \trim($matches[1]);
        }

        return null;
    }

    /**
     * Extract class name from file content
     */
    private function extractClassName(string $content): ?string
    {
        if (\preg_match('/(?:class|interface|trait)\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Resolve ClassReflection from node or file path
     */
    private function resolveClassReflection(Node $node): ?ClassReflection
    {
        if ($node->hasAttribute(AttributeKey::SCOPE)) {
            return ScopeFetcher::fetch($node)->getClassReflection();
        }

        return $this->getClassReflectionFromFilePath();
    }
}
