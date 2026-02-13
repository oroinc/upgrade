<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Renaming\Method;

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\MethodCallReplace;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\NodeManipulator\ClassManipulator;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Rector\Renaming\Contract\MethodCallRenameInterface;
use Rector\Renaming\ValueObject\MethodCallRenameWithArrayKey;
use RectorPrefix202507\Webmozart\Assert\Assert;

/**
 * Modified copy of \Rector\Renaming\Rector\MethodCall\RenameMethodRector, Rector v2.1.2
 *
 * Added ability of method call replacement with chained method calls
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
final class OroRenameMethodRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $methodCallReplaces = [];

    public function __construct(
        private readonly ClassManipulator $classManipulator,
        private readonly ReflectionResolver $reflectionResolver,
        private readonly ReflectionProvider $reflectionProvider
    ) {
    }

    public function getNodeTypes(): array
    {
        return [
            MethodCall::class,
            NullsafeMethodCall::class,
            StaticCall::class,
            Class_::class,
            Trait_::class,
            Interface_::class,
        ];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Class_ || $node instanceof Trait_ || $node instanceof Interface_) {
            $scope = ScopeFetcher::fetch($node);
            return $this->refactorClass($node, $scope);
        }
        return $this->refactorMethodCallAndStaticCall($node);
    }

    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, MethodCallRenameInterface::class);
        $this->methodCallReplaces = $configuration;
    }

    private function shouldSkipClassMethod(
        MethodCall|NullsafeMethodCall|StaticCall $call,
        MethodCallRenameInterface $methodCallRename
    ): bool {
        $classReflection = $this->reflectionResolver->resolveClassReflectionSourceObject($call);
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        $targetClass = $methodCallRename->getClass();
        if (!$this->reflectionProvider->hasClass($targetClass)) {
            return \false;
        }
        $targetClassReflection = $this->reflectionProvider->getClass($targetClass);
        if ($classReflection->getName() === $targetClassReflection->getName()) {
            return \false;
        }
        // different with configured ClassLike source? it is a child, which may has old and new exists
        if (!$classReflection->hasMethod($methodCallRename->getOldMethod())) {
            return \false;
        }
        return $classReflection->hasMethod($methodCallRename->getNewMethod());
    }

    private function hasClassNewClassMethod(
        Class_|Trait_|Interface_ $classOrInterface,
        MethodCallRenameInterface $methodCallRename
    ): bool {
        return (bool) $classOrInterface->getMethod($methodCallRename->getNewMethod());
    }

    private function shouldKeepForParentInterface(
        MethodCallRenameInterface $methodCallRename,
        ?ClassReflection $classReflection
    ): bool {
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        // interface can change current method, as parent contract is still valid
        if (!$classReflection->isInterface()) {
            return \false;
        }
        return $this->classManipulator
            ->hasParentMethodOrInterface($methodCallRename->getObjectType(), $methodCallRename->getOldMethod());
    }

    private function refactorClass(
        Class_|Trait_|Interface_ $classOrInterface,
        Scope $scope
    ): Class_|Trait_|Interface_|null {
        $classReflection = $scope->getClassReflection();
        $hasChanged = \false;
        foreach ($classOrInterface->getMethods() as $classMethod) {
            $methodName = $this->getName($classMethod->name);
            if ($methodName === null) {
                continue;
            }
            foreach ($this->methodCallReplaces as $methodCallRename) {
                if (
                    $this->shouldSkipRename(
                        $methodName,
                        $classMethod,
                        $methodCallRename,
                        $classOrInterface,
                        $classReflection
                    )
                ) {
                    continue;
                }
                $classMethod->name = new Identifier($methodCallRename->getNewMethod());
                $hasChanged = \true;
                continue 2;
            }
        }
        if ($hasChanged) {
            return $classOrInterface;
        }
        return null;
    }

    private function shouldSkipRename(
        string $methodName,
        ClassMethod $classMethod,
        MethodCallRenameInterface $methodCallRename,
        Class_|Trait_|Interface_ $classOrInterface,
        ?ClassReflection $classReflection
    ): bool {
        if (!$this->nodeNameResolver->isStringName($methodName, $methodCallRename->getOldMethod())) {
            return \true;
        }
        if (!$classReflection instanceof ClassReflection && $classOrInterface instanceof Trait_) {
            return $this->hasClassNewClassMethod($classOrInterface, $methodCallRename);
        }
        if (
            !$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType(
                $classMethod,
                $methodCallRename->getObjectType()
            )
        ) {
            return \true;
        }
        if ($this->shouldKeepForParentInterface($methodCallRename, $classReflection)) {
            return \true;
        }
        return $this->hasClassNewClassMethod($classOrInterface, $methodCallRename);
    }

    private function refactorMethodCallAndStaticCall(
        StaticCall|MethodCall|NullsafeMethodCall $call
    ): StaticCall|MethodCall|ArrayDimFetch|NullsafeMethodCall|null {
        $callName = $this->getName($call->name);
        if ($callName === null) {
            return null;
        }
        foreach ($this->methodCallReplaces as $methodCallRename) {
            if (!$this->nodeNameResolver->isStringName($callName, $methodCallRename->getOldMethod())) {
                continue;
            }
            if (
                !$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType(
                    $call,
                    $methodCallRename->getObjectType()
                )
            ) {
                continue;
            }
            if ($this->shouldSkipClassMethod($call, $methodCallRename)) {
                continue;
            }
            $call->name = new Identifier($methodCallRename->getNewMethod());

            // Support for MethodCallReplace with chained methods
            if ($methodCallRename instanceof MethodCallReplace) {
                return $this->buildMethodChain($call, $methodCallRename->getChainedMethods());
            }

            if ($methodCallRename instanceof MethodCallRenameWithArrayKey) {
                return new ArrayDimFetch($call, BuilderHelpers::normalizeValue($methodCallRename->getArrayKey()));
            }

            return $call;
        }
        return null;
    }

    /**
     * Build a chain of method calls from the base call
     */
    private function buildMethodChain(
        StaticCall|MethodCall|NullsafeMethodCall $baseCall,
        array $chainedMethods
    ): MethodCall {
        $currentCall = $baseCall;

        foreach ($chainedMethods as $methodName) {
            $currentCall = new MethodCall($currentCall, new Identifier($methodName));
        }

        return $currentCall;
    }
}
