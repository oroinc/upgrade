<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro60;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\If_;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AddUserTypeCheckWhileAuthRector extends AbstractRector
{
    #[\Override]
    public function getNodeTypes(): array
    {
        return [If_::class];
    }

    /**
     * @param If_ $node
     */
    #[\Override]
    public function refactor(Node $node): ?Node
    {
        $scope = ScopeFetcher::fetch($node);
        $classReflection = $scope->getClassReflection();

        if (is_null($classReflection)) {
            return null;
        }

        if (!$classReflection->isSubclassOf(AbstractController::class)) {
            return null;
        }

        $isStatementReversed = false;

        $methodCall = match (get_class($node->cond)) {
            // $this->getUser()
            MethodCall::class => $node->cond,
            // noll !== $this->getUser()
            // $this->getUser() !== null
            NotIdentical::class,
            // noll != $this->getUser()
            // $this->getUser() != null
            NotEqual::class => $this->getMethodCallFromBinaryOp($node->cond),
            // !is_null($this->getUser())
            BooleanNot::class => $this->getMethodCallFromNotIsNull($node->cond),
            default => null,
        };

        if (is_null($methodCall)) {
            $isStatementReversed = true;
            $methodCall = match (get_class($node->cond)) {
                // !$this->getUser()
                BooleanNot::class => $this->getMethodCallFromBooleanNot($node->cond),
                // noll === $this->getUser()
                // $this->getUser() === null
                Identical::class,
                // noll == $this->getUser()
                // $this->getUser() == null
                Equal::class => $this->getMethodCallFromBinaryOp($node->cond),
                // is_null($this->getUser())
                FuncCall::class => $this->getMethodCallFromIsNull($node->cond),
                default => null,
            };
        }

        if (!$this->validateMethodCall($methodCall)) {
            return null;
        }

        $node->cond = $this->makeNewCondition($methodCall, $isStatementReversed);

        return $node;
    }

    private function getMethodCallFromBinaryOp(BinaryOp $op): ?MethodCall
    {
        if (
            is_a($op->left, ConstFetch::class) &&
            $op->left->name->toString() === 'null' &&
            is_a($op->right, MethodCall::class)
        ) {
            return $op->right;
        } elseif (
            is_a($op->right, ConstFetch::class) &&
            $op->right->name->toString() === 'null' &&
            is_a($op->left, MethodCall::class)
        ) {
            return $op->left;
        }

        return null;
    }

    private function getMethodCallFromNotIsNull(BooleanNot $booleanNotExpr): ?MethodCall
    {
        if (!is_a($booleanNotExpr->expr, FuncCall::class)) {
            return null;
        }

        return $this->getMethodCallFromIsNull($booleanNotExpr->expr);
    }

    private function getMethodCallFromIsNull(FuncCall $funcCallExpr): ?MethodCall
    {
        if ($funcCallExpr->name->toString() !== 'is_null') {
            return null;
        }

        $argumentValue = $funcCallExpr->getArgs()[0]->value;

        if (!is_a($argumentValue, MethodCall::class)) {
            return null;
        }

        return $argumentValue;
    }

    private function getMethodCallFromBooleanNot(BooleanNot $booleanNotExpr): ?MethodCall
    {
        if (!is_a($booleanNotExpr->expr, MethodCall::class)) {
            return null;
        }

        return $booleanNotExpr->expr;
    }

    private function validateMethodCall(?MethodCall $methodCall): bool
    {
        return !is_null($methodCall) &&
            $methodCall->var->name === 'this' &&
            $methodCall->name->toString() === 'getUser';
    }

    private function makeNewCondition(MethodCall $methodCall, bool $shouldStatementBeReversed): Expr
    {
        $newCondition = new Instanceof_(
            $methodCall,
            new FullyQualified(AbstractUser::class)
        );

        if ($shouldStatementBeReversed) {
            $newCondition = new BooleanNot($newCondition);
        }

        return $newCondition;
    }
}
