<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use Rector\Rector\AbstractRector;

/**
 * Modernizes Template configuration by moving template path from setter to constructor.
 *
 * This rule optimizes Template object creation by eliminating unnecessary method calls
 * and utilizing the constructor parameter that accepts the template path directly.
 *
 * Before:
 * $template = new Template();
 * $template->setTemplate($templatePath);
 *
 * After:
 * $template = new Template($templatePath);
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class TemplateAttributeSetterToConstructorRector extends AbstractRector
{
    private const TEMPLATE_CLASSES = [
        'Sensio\Bundle\FrameworkExtraBundle\Configuration\Template',
        'Symfony\Bridge\Twig\Attribute\Template',
    ];

    private const SET_TEMPLATE_METHOD = 'setTemplate';

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        $this->processNode($node);

        return $node;
    }

    private function processNode(Node $node): void
    {
        if (!property_exists($node, 'stmts') || !is_array($node->stmts)) {
            return;
        }

        foreach ($node->stmts as $key => $stmt) {
            if ($stmt instanceof Expression) {
                $this->handleExpressionStatement($stmt, $node, $key);
            } elseif ($stmt instanceof Return_) {
                $this->handleReturnStatement($stmt, $node);
            } elseif (property_exists($stmt, 'stmts') && is_array($stmt->stmts) && count($stmt->stmts) > 0) {
                $this->processNode($stmt);
            }
        }
    }

    private function handleExpressionStatement(
        Expression $stmt,
        Node $node,
        int $key
    ): void {
        if (!$stmt->expr instanceof MethodCall) {
            return;
        }

        $methodCall = $stmt->expr;
        if (!$this->isSetTemplateMethodCall($methodCall)) {
            return;
        }

        if (count($methodCall->args) === 0) {
            return;
        }

        $varName = $methodCall->var->name;
        if ($varName instanceof \PhpParser\Node\Identifier) {
            $varName = $varName->name;
        }

        $arg = $methodCall->args[0];

        if ($this->setArgToConstructor($node, $varName, $arg)) {
            unset($node->stmts[$key]);
        }
    }

    private function handleReturnStatement(Return_ $stmt, Node $node): void
    {
        if (!$stmt->expr instanceof MethodCall) {
            return;
        }

        $methodCall = $stmt->expr;
        if (!$this->isSetTemplateMethodCall($methodCall)) {
            return;
        }

        if (count($methodCall->args) === 0) {
            return;
        }

        $varName = $methodCall->var->name;
        if ($varName instanceof \PhpParser\Node\Identifier) {
            $varName = $varName->name;
        }

        $arg = $methodCall->args[0];

        $variableKeyToRemove = $this->findVariableAssignment($node, $varName);

        $stmt->expr = new New_(
            new Name('Template'),
            [$arg]
        );

        if ($variableKeyToRemove !== null) {
            unset($node->stmts[$variableKeyToRemove]);
        }
    }

    private function isSetTemplateMethodCall(MethodCall $methodCall): bool
    {
        return self::SET_TEMPLATE_METHOD === $methodCall->name->name;
    }

    private function findVariableAssignment(Node $node, string $varName): ?int
    {
        foreach ($node->stmts as $key => $stmt) {
            if (!$stmt instanceof Expression ||
                !$stmt->expr instanceof Assign ||
                !$stmt->expr->expr instanceof New_) {
                continue;
            }

            $assign = $stmt->expr;
            if ($this->isTemplateClass($assign->expr->class->name) &&
                $varName === $assign->var->name) {
                return $key;
            }
        }

        return null;
    }

    private function setArgToConstructor(Node $node, string $varName, $arg): bool
    {
        if (!property_exists($node, 'stmts') || !is_array($node->stmts)) {
            return false;
        }

        foreach ($node->stmts as $stmt) {
            // Check for template assignment in current statement
            if ($this->tryUpdateTemplateAssignment($stmt, $varName, $arg)) {
                return true;
            }

            // Recursively check nested statements
            if (property_exists($stmt, 'stmts') &&
                is_array($stmt->stmts) &&
                count($stmt->stmts) > 0 &&
                $this->setArgToConstructor($stmt, $varName, $arg)) {
                return true;
            }
        }

        return false;
    }

    private function tryUpdateTemplateAssignment(Stmt $stmt, string $varName, $arg): bool
    {
        if (!$stmt instanceof Expression ||
            !$stmt->expr instanceof Assign ||
            !$stmt->expr->expr instanceof New_) {
            return false;
        }

        $assign = $stmt->expr;

        if (!$this->isTemplateClass($assign->expr->class->name) ||
            $varName !== $assign->var->name) {
            return false;
        }

        $argsCount = count($assign->expr->args);

        if ($argsCount === 0) {
            $assign->expr->args[] = $arg;
            return true;
        }

        if ($argsCount === 1) {
            $currentArg = $assign->expr->args[0];
            if ($currentArg->value !== $arg) {
                $newArg = $arg;
                $assign->expr->args[0] = $newArg;
                return true;
            }
        }

        return false;
    }

    private function isTemplateClass(string $className): bool
    {
        return in_array($className, self::TEMPLATE_CLASSES, true);
    }
}
