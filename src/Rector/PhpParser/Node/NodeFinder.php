<?php

namespace Oro\UpgradeToolkit\Rector\PhpParser\Node;

use Oro\UpgradeToolkit\Rector\PhpParser\AttributeKey;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use Rector\PhpParser\Node\BetterNodeFinder;

/**
 * Modified copy of\Rector\Core\PhpParser\Node\BetterNodeFinder, Rector v0.16.0
 * Provides excluded node finding methods
 *
 * Copyright (c) 2017-present Tomáš Votruba (https://tomasvotruba.cz)
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
class NodeFinder
{
    public const PREVIOUS_NODE = 'previous';

    public function __construct(
        protected readonly BetterNodeFinder $betterNodeFinder,
    ) {
    }

    /**
     * @template T of Node
     * @param array<class-string<T>> $types
     */
    public function findFirstPreviousOfTypes(Node $mainNode, array $types): ?Node
    {
        return $this->findFirstPrevious($mainNode, function (Node $node) use ($types): bool {
            return $this->isInstanceOf($node, $types);
        });
    }

    /**
     * @param array<class-string> $types
     */
    public function isInstanceOf(object $object, array $types): bool
    {
        foreach ($types as $type) {
            if ($object instanceof $type) {
                return \true;
            }
        }
        return \false;
    }

    /**
     * Search in previous Node/Stmt, when no Node found, lookup previous Stmt of Parent Node
     *
     * @param callable(Node $node): bool $filter
     */
    public function findFirstPrevious(Node $node, callable $filter): ?Node
    {
        $foundNode = $this->findFirstInlinedPrevious($node, $filter);
        // we found what we need
        if ($foundNode instanceof Node) {
            return $foundNode;
        }
        $parentNode = $node->getAttribute(AttributeKey::PARENT_NODE);
        if ($parentNode instanceof FunctionLike) {
            return null;
        }
        if ($parentNode instanceof Node) {
            return $this->findFirstPrevious($parentNode, $filter);
        }
        return null;
    }

    /**
     * Only search in previous Node/Stmt
     *
     * @param callable(Node $node): bool $filter
     */
    private function findFirstInlinedPrevious(Node $node, callable $filter): ?Node
    {
        $previousNode = $node->getAttribute(self::PREVIOUS_NODE);
        if (!$previousNode instanceof Node) {
            return null;
        }
        if ($previousNode === $node) {
            return null;
        }
        $foundNode = $this->betterNodeFinder->findFirst($previousNode, $filter);
        // we found what we need
        if ($foundNode instanceof Node) {
            return $foundNode;
        }
        return $this->findFirstInlinedPrevious($previousNode, $filter);
    }
}
