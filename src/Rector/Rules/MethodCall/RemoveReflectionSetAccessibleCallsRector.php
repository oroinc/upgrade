<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitor;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Rector\ValueObject\PhpVersion;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;

/**
 * Copy of \Rector\Php81\Rector\MethodCall\RemoveReflectionSetAccessibleCallsRector, Rector v2.3.1
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
final class RemoveReflectionSetAccessibleCallsRector extends AbstractRector implements MinPhpVersionInterface
{
    public function getNodeTypes(): array
    {
        return [Expression::class];
    }

    public function refactor(Node $node): ?int
    {
        if ($node->expr instanceof MethodCall === false) {
            return null;
        }
        $methodCall = $node->expr;
        if ($this->isName($methodCall->name, 'setAccessible') === false) {
            return null;
        }
        if (
            $this->isObjectType($methodCall->var, new ObjectType('ReflectionProperty'))
            || $this->isObjectType($methodCall->var, new ObjectType('ReflectionMethod'))
        ) {
            return NodeVisitor::REMOVE_NODE;
        }

        return null;
    }

    public function provideMinPhpVersion(): int
    {
        return PhpVersion::PHP_81;
    }
}
