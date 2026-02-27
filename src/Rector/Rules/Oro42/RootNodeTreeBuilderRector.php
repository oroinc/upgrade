<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro42;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Type\ObjectType;
use Rector\PhpParser\Enum\NodeGroup;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\Rector\AbstractRector;

/**
 * @changelog https://github.com/symfony/symfony/pull/27476
 *
 * Modified copy of \Rector\Symfony\Symfony42\Rector\New_\RootNodeTreeBuilderRector, Rector v1.0.3
 *
 *  Copyright (c) 2017-present Tomáš Votruba (https://tomasvotruba.cz)
 *
 *  Permission is hereby granted, free of charge, to any person
 *  obtaining a copy of this software and associated documentation
 *  files (the "Software"), to deal in the Software without
 *  restriction, including without limitation the rights to use,
 *  copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the
 *  Software is furnished to do so, subject to the following
 *  conditions:
 *
 *  The above copyright notice and this permission notice shall be
 *  included in all copies or substantial portions of the Software.
 */
final class RootNodeTreeBuilderRector extends AbstractRector
{
    private const TREE_BUILDER = 'Symfony\\Component\\Config\\Definition\\Builder\\TreeBuilder';

    public function __construct(
        private readonly BetterNodeFinder $betterNodeFinder
    ) {
    }

    #[\Override]
    public function getNodeTypes(): array
    {
        return NodeGroup::STMTS_AWARE;
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        if ($node->stmts === null) {
            return null;
        }

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof Assign) {
                continue;
            }
            $assign = $stmt->expr;
            if (!$assign->expr instanceof New_) {
                continue;
            }
            $new = $assign->expr;
            // already has first arg
            if (isset($new->getArgs()[1])) {
                continue;
            }
            if (!$this->isObjectType($new->class, new ObjectType(self::TREE_BUILDER))) {
                continue;
            }

            $rootMethodCallNode = $this->getRootMethodCallNode($node);
            if (!$rootMethodCallNode instanceof MethodCall) {
                return null;
            }

            [$new->args, $rootMethodCallNode->args] = [$rootMethodCallNode->getArgs(), $new->getArgs()];
            $rootMethodCallNode->name = new Identifier('getRootNode');

            return $node;
        }

        return null;
    }

    private function getRootMethodCallNode(Node $stmtsAware): ?Node
    {
        $methodCalls = $this->betterNodeFinder->findInstanceOf($stmtsAware, MethodCall::class);
        foreach ($methodCalls as $methodCall) {
            if (!$this->isName($methodCall->name, 'root')) {
                continue;
            }
            if (!$this->isObjectType($methodCall->var, new ObjectType(self::TREE_BUILDER))) {
                continue;
            }
            if (!isset($methodCall->getArgs()[0])) {
                continue;
            }

            return $methodCall;
        }

        return null;
    }
}
