<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\Doctrine;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Reflection\ClassReflection;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;

/**
 * Replace useResultCache with enableResultCache and adjust arguments
 *
 * Example:
 *
 * - Before: ->useResultCache(true, 3600, 'cache_key')
 * - After:  ->enableResultCache(3600, 'cache_key')
 *
 *  - Before: ->useResultCache(false, 3600, 'cache_key')
 *  - After:  ->disableResultCache()
 */
final class ReplaceUseResultCacheRector extends AbstractRector
{
    private const CLASS_NAME = 'Doctrine\\ORM\\AbstractQuery';
    private const METHOD_NAME = 'useResultCache';

    public function __construct(
        private readonly ReflectionResolver $reflectionResolver
    ) {
    }

    #[\Override]
    public function getNodeTypes(): array
    {
        return [
            MethodCall::class,
            NullsafeMethodCall::class
        ];
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        $classReflection = $this->reflectionResolver->resolveClassReflectionSourceObject($node);
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }

        if (self::CLASS_NAME !== $classReflection->getName()) {
            return null;
        }

        if (!$classReflection->hasMethod(self::METHOD_NAME)) {
            return null;
        }

        if (isset($node->args[0])) {
            return $this->replaceUseResultCacheCall($node);
        }

        return null;
    }

    private function replaceUseResultCacheCall(Node $node): ?Node
    {
        $firstArg = $node->args[0];
        if (!$firstArg->value instanceof Node\Expr\ConstFetch) {
            return null;
        }

        if ($this->isName($firstArg->value->name, 'true')) {
            $node->name = new Identifier('enableResultCache');
            // Remove first argument and shift others
            array_shift($node->args);
            $node->args = array_values($node->args);

            return $node;
        }

        if ($this->isName($firstArg->value->name, 'false')) {
            $node->name = new Identifier('disableResultCache');
            $node->args = [];

            return $node;
        }

        return null;
    }
}
