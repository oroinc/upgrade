<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use Rector\Rector\AbstractRector;

/**
 * Refactors dynamic enum repository access
 * by replacing class name resolution with EnumOption::class
 * and ID generation using buildEnumOptionId()
 */
final class ReplaceDynamicEnumClassInRepositoryFindRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        // Look for ...->getRepository(...)->find(...)
        if ($this->isName($node->name, 'find') && $node->var instanceof MethodCall) {
            $getRepositoryCall = $node->var;

            if (!$this->isName($getRepositoryCall->name, 'getRepository')) {
                return null;
            }

            $args = $getRepositoryCall->args;
            if (count($args) !== 1 || !$args[0]->value instanceof StaticCall) {
                return null;
            }

            $staticCall = $args[0]->value;
            if (
                $this->isName($staticCall->name, 'buildEnumValueClassName') &&
                $staticCall->class instanceof Name &&
                $this->getName($staticCall->class) === 'Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper'
            ) {
                $enumCodeExpr = $staticCall->args[0]->value ?? null;
                $valueExpr = $node->args[0]->value ?? null;

                if ($enumCodeExpr && $valueExpr) {
                    // Step 1: Update argument getRepository(...)
                    $getRepositoryCall->args[0]->value = $this->nodeFactory->createClassConstReference(
                        'Oro\Bundle\EntityExtendBundle\Entity\EnumOption'
                    );

                    //  Step 2: Update argument  find(...)
                    $node->args[0]->value = $this->nodeFactory->createStaticCall(
                        'Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper',
                        'buildEnumOptionId',
                        [$enumCodeExpr, $valueExpr]
                    );

                    return $node;
                }
            }
        }

        return null;
    }
}
