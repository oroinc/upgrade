<?php

declare (strict_types=1);

namespace Oro\UpgradeToolkit\Rector\PhpParser\Node\Value;

use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ConstantType;
use PHPStan\Type\TypeWithClassName;
use Rector\Application\Provider\CurrentFileProvider;
use Rector\Enum\ObjectReference;
use Rector\Exception\ShouldNotHappenException;
use Rector\NodeAnalyzer\ConstFetchAnalyzer;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\NodeTypeResolver;
use Rector\Reflection\ClassReflectionAnalyzer;
use Rector\Reflection\ReflectionResolver;
use TypeError;

/**
 * Modified copy of \Rector\PhpParser\Node\Value\ValueResolver, Rector v1.0.3
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
 * @SuppressWarnings(PHPMD)
 */
final class ValueResolver
{
    private ?ConstExprEvaluator $constExprEvaluator = null;

    public function __construct(
        private readonly NodeNameResolver $nodeNameResolver,
        private readonly NodeTypeResolver $nodeTypeResolver,
        private readonly ConstFetchAnalyzer $constFetchAnalyzer,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly CurrentFileProvider $currentFileProvider,
        private readonly ReflectionResolver $reflectionResolver,
        private readonly ClassReflectionAnalyzer $classReflectionAnalyzer,
    ) {
    }

    /**
     * @param mixed $value
     */
    public function isValue(Expr $expr, $value): bool
    {
        return $this->getValue($expr) === $value;
    }

    public function getValue(Expr|Arg $expr, bool $resolvedClassReference = \false): mixed
    {
        if ($expr instanceof Arg) {
            $expr = $expr->value;
        }
        if ($expr instanceof Concat) {
            return $this->processConcat($expr, $resolvedClassReference);
        }
        if ($expr instanceof ClassConstFetch && $resolvedClassReference) {
            $class = $this->nodeNameResolver->getName($expr->class);
            if (\in_array($class, [ObjectReference::SELF, ObjectReference::STATIC], \true)) {
                $classReflection = $this->reflectionResolver->resolveClassReflection($expr);
                if ($classReflection instanceof ClassReflection) {
                    return $classReflection->getName();
                }
            }
            if ($this->nodeNameResolver->isName($expr->name, 'class')) {
                return $class;
            }
        }
        $value = $this->resolveExprValueForConst($expr);
        if ($value !== null) {
            return $value;
        }
        if ($expr instanceof ConstFetch) {
            return $this->nodeNameResolver->getName($expr);
        }
        $nodeStaticType = $this->nodeTypeResolver->getType($expr);
        if ($nodeStaticType instanceof ConstantType) {
            return $this->resolveConstantType($nodeStaticType);
        }
        return null;
    }

    public function isValues(Expr $expr, array $expectedValues): bool
    {
        foreach ($expectedValues as $expectedValue) {
            if ($this->isValue($expr, $expectedValue)) {
                return \true;
            }
        }
        return \false;
    }

    public function isFalse(Expr $expr): bool
    {
        return $this->constFetchAnalyzer->isFalse($expr);
    }

    public function isTrueOrFalse(Expr $expr): bool
    {
        return $this->constFetchAnalyzer->isTrueOrFalse($expr);
    }

    public function isTrue(Expr $expr): bool
    {
        return $this->constFetchAnalyzer->isTrue($expr);
    }

    public function isNull(Expr $expr): bool
    {
        return $this->constFetchAnalyzer->isNull($expr);
    }

    public function areValuesEqual(array $nodes, array $expectedValues): bool
    {
        foreach ($nodes as $i => $node) {
            if (!$node instanceof Expr) {
                return \false;
            }
            if (!$this->isValue($node, $expectedValues[$i])) {
                return \false;
            }
        }
        return \true;
    }

    private function resolveExprValueForConst(Expr $expr): mixed
    {
        try {
            $constExprEvaluator = $this->getConstExprEvaluator();
            return $constExprEvaluator->evaluateDirectly($expr);
        } catch (ConstExprEvaluationException|TypeError $exception) {
        }
        return null;
    }

    private function processConcat(Concat $concat, bool $resolvedClassReference): string
    {
        return $this->getValue($concat->left, $resolvedClassReference) . $this->getValue($concat->right, $resolvedClassReference);
    }

    private function getConstExprEvaluator(): ConstExprEvaluator
    {
        if ($this->constExprEvaluator instanceof ConstExprEvaluator) {
            return $this->constExprEvaluator;
        }
        $this->constExprEvaluator = new ConstExprEvaluator(function (Expr $expr) {
            if ($expr instanceof Dir) {
                // __DIR__
                return $this->resolveDirConstant();
            }
            if ($expr instanceof File) {
                // __FILE__
                return $this->resolveFileConstant($expr);
            }
            // resolve "SomeClass::SOME_CONST"
            if ($expr instanceof ClassConstFetch && $expr->class instanceof Name) {
                return $this->resolveClassConstFetch($expr);
            }
            throw new ConstExprEvaluationException(\sprintf('Expression of type "%s" cannot be evaluated', $expr->getType()));
        });
        return $this->constExprEvaluator;
    }

    private function extractConstantArrayTypeValue(ConstantArrayType $constantArrayType): ?array
    {
        $keys = [];
        foreach ($constantArrayType->getKeyTypes() as $i => $keyType) {
            /** @var ConstantScalarType $keyType */
            $keys[$i] = $keyType->getValue();
        }
        $values = [];
        foreach ($constantArrayType->getValueTypes() as $i => $valueType) {
            if ($valueType instanceof ConstantArrayType) {
                $value = $this->extractConstantArrayTypeValue($valueType);
            } elseif ($valueType instanceof ConstantScalarType) {
                $value = $valueType->getValue();
            } elseif ($valueType instanceof TypeWithClassName) {
                continue;
            } else {
                return null;
            }
            $values[$keys[$i]] = $value;
        }
        return $values;
    }

    private function resolveDirConstant(): string
    {
        $file = $this->currentFileProvider->getFile();
        if (!$file instanceof \Rector\ValueObject\Application\File) {
            throw new ShouldNotHappenException();
        }
        return \dirname($file->getFilePath());
    }

    private function resolveFileConstant(File $file): string
    {
        $file = $this->currentFileProvider->getFile();
        if (!$file instanceof \Rector\ValueObject\Application\File) {
            throw new ShouldNotHappenException();
        }
        return $file->getFilePath();
    }

    private function resolveClassConstFetch(ClassConstFetch $classConstFetch)
    {
        $class = $this->nodeNameResolver->getName($classConstFetch->class);
        $constant = $this->nodeNameResolver->getName($classConstFetch->name);
        if ($class === null) {
            throw new ShouldNotHappenException();
        }
        if ($constant === null) {
            throw new ShouldNotHappenException();
        }
        if (\in_array($class, [ObjectReference::SELF, ObjectReference::STATIC, ObjectReference::PARENT], \true)) {
            $class = $this->resolveClassFromSelfStaticParent($classConstFetch, $class);
        }
        if ($constant === 'class') {
            return $class;
        }
        $classConstantReference = $class . '::' . $constant;

        // \constant call can cause a fatal error if event class inherits non-existent class
        //        if (\defined($classConstantReference)) {
        //            return \constant($classConstantReference);
        //        }

        if (!$this->reflectionProvider->hasClass($class)) {
            // fallback to constant reference itself, to avoid fatal error
            return $classConstantReference;
        }
        $classReflection = $this->reflectionProvider->getClass($class);
        if (!$classReflection->hasConstant($constant)) {
            // fallback to constant reference itself, to avoid fatal error
            return $classConstantReference;
        }
        if ($classReflection->isEnum()) {
            // fallback to constant reference itself, to avoid fatal error
            return $classConstantReference;
        }
        $constantReflection = $classReflection->getConstant($constant);
        $valueExpr = $constantReflection->getValueExpr();
        if ($valueExpr instanceof ConstFetch) {
            return $this->resolveExprValueForConst($valueExpr);
        }
        return $this->getValue($valueExpr);
    }

    private function resolveClassFromSelfStaticParent(ClassConstFetch $classConstFetch, string $class): string
    {
        // Scope may be loaded too late, so return empty string early
        // it will be resolved on next traverse
        $scope = $classConstFetch->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            return '';
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($classConstFetch);
        if (!$classReflection instanceof ClassReflection) {
            throw new ShouldNotHappenException('Complete class parent node for to class const fetch, so "self" or "static" references is resolvable to a class name');
        }
        if ($class !== ObjectReference::PARENT) {
            return $classReflection->getName();
        }
        if (!$classReflection->isClass()) {
            throw new ShouldNotHappenException('Complete class parent node for to class const fetch, so "parent" references is resolvable to lookup parent class');
        }
        // ensure parent class name still resolved even not autoloaded
        $parentClassName = $this->classReflectionAnalyzer->resolveParentClassName($classReflection);
        if ($parentClassName === null) {
            throw new ShouldNotHappenException();
        }
        return $parentClassName;
    }

    private function resolveConstantType(ConstantType $constantType)
    {
        if ($constantType instanceof ConstantArrayType) {
            return $this->extractConstantArrayTypeValue($constantType);
        }
        if ($constantType instanceof ConstantScalarType) {
            return $constantType->getValue();
        }
        return null;
    }
}
