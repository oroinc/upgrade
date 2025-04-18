<?php

declare (strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro42;

use Oro\UpgradeToolkit\Rector\PhpParser\Node\Value\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\ObjectType;
use Rector\NodeTypeResolver\TypeAnalyzer\StringTypeAnalyzer;
use Rector\Rector\AbstractRector;

/**
 * @changelog https://symfony.com/blog/new-in-symfony-4-3-simpler-event-dispatching
 *
 * Modified copy of \Rector\Symfony\Symfony43\Rector\MethodCall\MakeDispatchFirstArgumentEventRector, Rector v1.0.3
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
final class MakeDispatchFirstArgumentEventRector extends AbstractRector
{
    public function __construct(
        private readonly StringTypeAnalyzer $stringTypeAnalyzer,
        private readonly ValueResolver $valueResolver,
    ) {
    }

    /**
     * @return array<class-string<Node>>
     */
    #[\Override]
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    #[\Override]
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        $firstArg = $node->args[0];
        if (!$firstArg instanceof Arg) {
            return null;
        }
        $firstArgumentValue = $firstArg->value;
        if ($this->stringTypeAnalyzer->isStringOrUnionStringOnlyType($firstArgumentValue)) {
            $this->refactorStringArgument($node);
            return $node;
        }
        $secondArg = $node->args[1];
        if (!$secondArg instanceof Arg) {
            return null;
        }
        $secondArgumentValue = $secondArg->value;
        if ($secondArgumentValue instanceof FuncCall) {
            $this->refactorGetCallFuncCall($node, $secondArgumentValue, $firstArgumentValue);
            return $node;
        }
        return null;
    }

    private function shouldSkip(MethodCall $methodCall): bool
    {
        if (!$this->isName($methodCall->name, 'dispatch')) {
            return \true;
        }
        if (!$this->isObjectType($methodCall->var, new ObjectType('Symfony\\Contracts\\EventDispatcher\\EventDispatcherInterface'))) {
            return \true;
        }
        return !isset($methodCall->args[1]);
    }

    private function refactorStringArgument(MethodCall $methodCall): void
    {
        // swap arguments
        [$methodCall->args[0], $methodCall->args[1]] = [$methodCall->args[1], $methodCall->args[0]];
        if ($this->isEventNameSameAsEventObjectClass($methodCall)) {
            unset($methodCall->args[1]);
        }
    }

    private function refactorGetCallFuncCall(MethodCall $methodCall, FuncCall $funcCall, Expr $expr): void
    {
        if (!$this->isName($funcCall, 'get_class')) {
            return;
        }
        $firstArg = $funcCall->args[0];
        if (!$firstArg instanceof Arg) {
            return;
        }
        $getClassArgumentValue = $firstArg->value;
        if (!$this->nodeComparator->areNodesEqual($expr, $getClassArgumentValue)) {
            return;
        }
        unset($methodCall->args[1]);
    }

    /**
     * Is the event name just `::class`? We can remove it
     */
    private function isEventNameSameAsEventObjectClass(MethodCall $methodCall): bool
    {
        $secondArg = $methodCall->args[1];
        if (!$secondArg instanceof Arg) {
            return \false;
        }
        if (!$secondArg->value instanceof ClassConstFetch) {
            return \false;
        }
        $classConst = $this->valueResolver->getValue($secondArg->value);
        $firstArg = $methodCall->args[0];
        if (!$firstArg instanceof Arg) {
            return \false;
        }
        $eventStaticType = $this->getType($firstArg->value);
        if (!$eventStaticType instanceof ObjectType) {
            return \false;
        }
        return $classConst === $eventStaticType->getClassName();
    }
}
