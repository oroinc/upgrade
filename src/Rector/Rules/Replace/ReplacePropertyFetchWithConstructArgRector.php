<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Replace;

use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\PropertyFetchWithConstructArgReplace;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Webmozart\Assert\Assert;

/**
 * Replaces property assignments after object instantiation with named constructor arguments.
 *
 * This rule transforms code where properties are set after creating an object
 * into a single constructor call with named arguments.
 *
 * Before:
 * $query = new Query();
 * $query->select = ['id', 'name'];
 * $query->from = 'users';
 *
 * After:
 * $query = new Query(select: ['id', 'name'], from: 'users');
 */
class ReplacePropertyFetchWithConstructArgRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $configurations = [];
    private $assign;

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    public function configure(array $configuration): void
    {
        Assert::allIsInstanceOf($configuration, PropertyFetchWithConstructArgReplace::class);
        $this->configurations = $configuration;
    }

    public function refactor(Node $node)
    {
        $hasChanged = false;

        foreach ($this->configurations as $config) {
            // Collect all applicable positions first to avoid index shifting issues
            $applicablePositions = [];
            foreach ($node->stmts ?? [] as $pos => $stmt) {
                if ($this->isApplicableAt($stmt, $config->getClass())) {
                    $applicablePositions[] = ['pos' => $pos, 'assign' => $this->assign, 'config' => $config];
                }
            }

            // Process in reverse order to maintain correct indices when removing statements
            foreach (array_reverse($applicablePositions) as $item) {
                $this->assign = $item['assign'];
                if ($this->replaceForStatement($node, $item['pos'], $item['config'])) {
                    $hasChanged = true;
                }
            }
        }

        return $hasChanged ? $node : null;
    }

    private function isApplicableAt($stmt, mixed $fqcn): bool
    {
        if ($stmt instanceof Expression && $stmt->expr instanceof Assign) {
            $assign = $stmt->expr;
            if ($assign->expr instanceof New_ && $fqcn === $assign->expr->class->name) {
                $this->assign = $assign;

                return true;
            }
        }

        return false;
    }

    private function replaceForStatement(
        Node $node,
        int $startPos,
        PropertyFetchWithConstructArgReplace $config
    ): bool {
        $varName = $this->assign->var->name;
        $endPos = $this->findEndPosition($node, $startPos, $varName);
        $className = $this->assign->expr->class->toString();

        $validParams = $this->getValidPropertyNames($className, $config);
        if ($validParams === null) {
            return false;
        }

        $positionsToRemove = $this->collectPropertyAssignments(
            $node,
            $startPos,
            $endPos,
            $varName,
            $validParams
        );

        if (empty($positionsToRemove)) {
            return false;
        }

        $this->removeProcessedStatements($node, $positionsToRemove);

        return true;
    }

    /**
     * Collect property assignments and add them as constructor arguments
     */
    private function collectPropertyAssignments(
        Node $node,
        int $startPos,
        int $endPos,
        string $varName,
        array $validParams
    ): array {
        $positionsToRemove = [];

        for ($pos = $startPos + 1; $pos < $endPos; $pos++) {
            $stmt = $node->stmts[$pos] ?? null;

            if (!$this->isPropertyAssignment($stmt, $varName)) {
                continue;
            }

            $propertyFetch = $stmt->expr->var;
            $propertyName = $propertyFetch->name->name;

            if (!in_array($propertyName, $validParams, true)) {
                continue;
            }

            $value = $stmt->expr->expr;

            $node->stmts[$startPos]->expr->expr->args[] = new Arg(
                value: $value,
                name: new Identifier($propertyName),
            );

            $positionsToRemove[] = $pos;
        }

        return $positionsToRemove;
    }

    /**
     * Check if statement is a property assignment to the specified variable
     */
    private function isPropertyAssignment($stmt, string $varName): bool
    {
        if (!$stmt instanceof Expression) {
            return false;
        }

        if (!$stmt->expr instanceof Assign) {
            return false;
        }

        if (!$stmt->expr->var instanceof Node\Expr\PropertyFetch) {
            return false;
        }

        $propertyFetch = $stmt->expr->var;

        return $propertyFetch->var instanceof Node\Expr\Variable
            && $propertyFetch->var->name === $varName;
    }

    /**
     * Remove processed statements in reverse order to maintain indices
     */
    private function removeProcessedStatements(Node $node, array $positionsToRemove): void
    {
        foreach (array_reverse($positionsToRemove) as $pos) {
            unset($node->stmts[$pos]);
        }
    }

    /**
     * Get valid property names for transformation based on configuration
     */
    private function getValidPropertyNames(string $className, PropertyFetchWithConstructArgReplace $config): ?array
    {
        $constructorParams = $this->getConstructorParamNames($className);
        if ($constructorParams === null) {
            return null;
        }

        if ($config->hasExplicitProperties()) {
            $configuredProperties = $config->getProperties();
            $validProperties = array_intersect($configuredProperties, $constructorParams);

            return array_values($validProperties);
        }

        // Otherwise, use all constructor parameters (auto-detect mode)
        return $constructorParams;
    }

    /**
     * Get constructor parameter names for a given class
     */
    private function getConstructorParamNames(string $className): ?array
    {
        try {
            if (!class_exists($className)) {
                return null;
            }

            $reflectionClass = new \ReflectionClass($className);
            $constructor = $reflectionClass->getConstructor();

            if (!$constructor) {
                return [];
            }

            $paramNames = [];
            foreach ($constructor->getParameters() as $param) {
                $paramNames[] = $param->getName();
            }

            return $paramNames;
        } catch (\ReflectionException $e) {
            return null;
        }
    }

    private function findEndPosition(Node $node, int $startPos, string $varName): int
    {
        $stmts = $node->stmts ?? [];
        $stmtsCount = count($stmts);

        for ($pos = $startPos + 1; $pos < $stmtsCount; $pos++) {
            $stmt = $stmts[$pos] ?? null;

            if ($stmt instanceof Expression && $stmt->expr instanceof Assign) {
                if (
                    $stmt->expr->var instanceof Node\Expr\Variable
                    && $stmt->expr->var->name === $varName
                ) {
                    return $pos;
                }
            }
        }

        return $stmtsCount;
    }
}
