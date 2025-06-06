<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum;

use Oro\UpgradeToolkit\Rector\PhpParser\AttributeKey;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use Rector\Rector\AbstractRector;

/**
 * Refactors calls to findBy/findOneBy methods on repositories obtained via
 * ExtendHelper::buildEnumValueClassName(),
 * replacing them with calls to the static EnumOption::class repository.
 * Additionally, it transforms search criteria by replacing
 * ['name' => $value] with ['id' => ExtendHelper::buildEnumOptionId($enumCode, $value)].
 */
final class ReplaceDynamicEnumClassInRepositoryFindByRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function refactor(Node $node): ?Node
    {
        if (
            !$node instanceof MethodCall ||
            !$this->isNames($node->name, ['findOneBy', 'findBy'])
        ) {
            return null;
        }

        [$getRepositoryCall, $enumCodeExpr] = $this->resolveRepositoryAndEnumCode($node->var);
        if (!$getRepositoryCall || !$enumCodeExpr) {
            return null;
        }

        $criteriaArg = $node->args[0] ?? null;
        if (!$criteriaArg || !$criteriaArg->value instanceof Array_) {
            return null;
        }

        $newItems = [];
        foreach ($criteriaArg->value->items as $item) {
            if (!$item instanceof ArrayItem || !$item->key instanceof String_) {
                $newItems[] = $item;
                continue;
            }

            $key = $item->key->value;

            if ('id' === $key) {
                continue;
            }

            if ('name' === $key) {
                $newItems[] = new ArrayItem(
                    $this->nodeFactory->createStaticCall(
                        'Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper',
                        'buildEnumOptionId',
                        [$enumCodeExpr, $item->value]
                    ),
                    new String_('id')
                );
                continue;
            }

            $newItems[] = $item;
        }

        $getRepositoryCall->args[0]->value = $this->nodeFactory->createClassConstReference(
            'Oro\Bundle\EntityExtendBundle\Entity\EnumOption'
        );

        $node->args[0]->value = new Array_($newItems);

        return $node;
    }

    private function resolveRepositoryAndEnumCode(Expr $var): array
    {
        if ($var instanceof MethodCall) {
            if (!$this->isName($var->name, 'getRepository')) {
                return [null, null];
            }

            $args = $var->args;
            if (count($args) !== 1 || !$args[0]->value instanceof StaticCall) {
                return [null, null];
            }

            $staticCall = $args[0]->value;
            if (!$this->isEnumValueClassNameStaticCall($staticCall)) {
                return [null, null];
            }

            return [$var, $staticCall->args[0]->value ?? null];
        }

        if ($var instanceof Variable && is_string($var->name)) {
            return $this->resolveEnumCodeFromRepositoryVariable($var);
        }

        return [null, null];
    }

    private function isEnumValueClassNameStaticCall(StaticCall $staticCall): bool
    {
        return $this->isName($staticCall->name, 'buildEnumValueClassName')
            && $staticCall->class instanceof Name
            && $this->getName($staticCall->class) === 'Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper';
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function resolveEnumCodeFromRepositoryVariable(Variable $var): array
    {
        $classMethod = $this->resolveEnclosingClassMethod($var);
        if (!$classMethod || $classMethod->stmts === null) {
            return [null, null];
        }

        foreach ($classMethod->stmts as $stmt) {
            if (!$stmt instanceof Expression || !$stmt->expr instanceof Assign) {
                continue;
            }

            $assign = $stmt->expr;

            if (!$assign->var instanceof Variable || $this->getName($assign->var) !== $this->getName($var)) {
                continue;
            }

            if (!$assign->expr instanceof MethodCall) {
                continue;
            }

            $repositoryCall = $assign->expr;
            if (!$this->isName($repositoryCall->name, 'getRepository')) {
                continue;
            }

            if (count($repositoryCall->args) !== 1) {
                continue;
            }

            $repoArg = $repositoryCall->args[0]->value;
            if (!$repoArg instanceof StaticCall || !$this->isEnumValueClassNameStaticCall($repoArg)) {
                continue;
            }

            return [$repositoryCall, $repoArg->args[0]->value ?? null];
        }

        return [null, null];
    }

    private function resolveEnclosingClassMethod(Node $node): ?ClassMethod
    {
        $current = $node;
        while ($parent = $current->getAttribute(AttributeKey::PARENT_NODE)) {
            if ($parent instanceof ClassMethod) {
                return $parent;
            }
            $current = $parent;
        }

        return null;
    }
}
