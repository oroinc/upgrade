<?php

namespace Oro\UpgradeToolkit\Rector\Replacement;

use Oro\UpgradeToolkit\Rector\PhpParser\Node\Value\ValueResolver;
use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\Contract\ArgumentReplacementInterface;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use Rector\NodeAnalyzer\ArgsAnalyzer;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\PhpParser\Node\NodeFactory;
use Rector\PHPStan\ScopeFetcher;
use Rector\Reflection\ReflectionResolver;

/**
 * Helper that replaces a single argument in a call-like node (method/static/attribute), based on configuration.
 *
 * How it works:
 * - If the node uses named arguments, it tries to locate the argument by name and verifies that its resolved value
 *   equals the configured "old" value.
 * - Otherwise it resolves the parameter position by name via PHPStan reflection and performs the same value check
 *   on the positional argument.
 * - If the check passes, it replaces the argument value with the configured "new" value (preserving the argument
 *   name for named arguments).
 *
 * Value comparison uses {@see ValueResolver} to evaluate constant-like expressions. Best supported values are:
 * scalars (string/int/float/bool), null, and arrays consisting of constant values;
 * {@see Expr} is supported as long as it can be resolved to a constant (e.g. const/class-const fetch, concatenation).
 * If values cannot be resolved, the replacement is skipped.
 *
 * Returns the modified node, or null when no matching argument/value was found.
 */
class ArgumentReplaceHelper
{
    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
        private readonly ValueResolver $valueResolver,
        private readonly ArgsAnalyzer $argsAnalyzer,
        private readonly NodeNameResolver $nodeNameResolver,
        private readonly NodeFactory $nodeFactory,
    ) {
    }

    public function replace(Node $node, ArgumentReplacementInterface $replacement): ?Node
    {
        [$position, $isNamed] = $this->resolveArgumentPosition($node, $replacement);

        if (null === $position || !isset($node->args[$position])) {
            return null;
        }

        $currentArg = $node->args[$position];
        if (!$currentArg instanceof Arg) {
            return null;
        }

        // For named arguments we already validated the old value while locating the argument.
        if (!$isNamed && !$this->compareValues($currentArg->value, $replacement->getOldValue())) {
            return null;
        }

        $node->args[$position] = $this->createReplacementArg($replacement, $isNamed);

        return $node;
    }

    /**
     * @return array{0:int|null,1:bool} [position, isNamed]
     */
    private function resolveArgumentPosition(Node $node, ArgumentReplacementInterface $replacement): array
    {
        $namedPosition = $this->resolveNamedArgumentPosition($node, $replacement);
        if ($namedPosition !== null) {
            return [$namedPosition, true];
        }

        return [$this->resolvePositionalArgumentPosition($node, $replacement), false];
    }

    private function resolveNamedArgumentPosition(Node $node, ArgumentReplacementInterface $replacement): ?int
    {
        if (!$this->argsAnalyzer->hasNamedArg($node->args)) {
            return null;
        }

        foreach ($node->args as $index => $arg) {
            if ($this->nodeNameResolver->getName($arg) !== $replacement->getArgName()) {
                continue;
            }

            if ($this->compareValues($arg->value, $replacement->getOldValue())) {
                return $index;
            }
        }

        return null;
    }

    private function resolvePositionalArgumentPosition(Node $node, ArgumentReplacementInterface $replacement): ?int
    {
        $methodReflection = $this->reflectionResolver->resolveMethodReflection(
            $replacement->getClass(),
            $replacement->getMethod(),
            ScopeFetcher::fetch($node)
        );

        if (!$methodReflection) {
            return null;
        }

        $variants = $methodReflection->getVariants();
        if ($variants === []) {
            return null;
        }

        $parameters = $variants[0]->getParameters();
        foreach ($parameters as $index => $parameter) {
            if ($parameter->getName() === $replacement->getArgName()) {
                return $index;
            }
        }

        return null;
    }

    private function createReplacementArg(ArgumentReplacementInterface $replacement, bool $isNamed): Arg
    {
        $newArg = $this->nodeFactory->createArg($replacement->getNewValue());
        if ($isNamed) {
            $newArg->name = new Identifier($replacement->getArgName());
        }

        return $newArg;
    }

    private function compareValues(mixed $currentValue, mixed $oldValue): bool
    {
        if ($currentValue instanceof Expr) {
            $currentValue = $this->valueResolver->getValue($currentValue);
        } else {
            $currentValue = $this->valueResolver->getValue(
                $this->nodeFactory->createArg($currentValue)
            );
        }

        if ($oldValue instanceof Expr) {
            $oldValue = $this->valueResolver->getValue($oldValue->value);
        } else {
            $oldValue = $this->valueResolver->getValue(
                $this->nodeFactory->createArg($oldValue)
            );
        }

        return $currentValue === $oldValue;
    }
}
