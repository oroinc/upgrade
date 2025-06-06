<?php

namespace Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;

/**
 * Replaces dynamic enum class resolution
 * in repository findAll() calls
 * with direct usage of EnumOption::class and a findBy on enumCode
 */
final class ReplaceDynamicEnumFindAllWithEnumOptionFindByRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        if (!$this->isName($node->name, 'findAll')) {
            return null;
        }

        $getRepositoryCall = $node->var;
        if (!$getRepositoryCall instanceof MethodCall
            || !$this->isName($getRepositoryCall->name, 'getRepository')
        ) {
            return null;
        }

        $getRepositoryArgs = $getRepositoryCall->args;
        if (
            count($getRepositoryArgs) !== 1
            || !$getRepositoryArgs[0]->value instanceof StaticCall
        ) {
            return null;
        }

        $staticCall = $getRepositoryArgs[0]->value;
        if (
            !$this->isName($staticCall->name, 'buildEnumValueClassName')
            || !$staticCall->class instanceof Name
            || $this->getName($staticCall->class) !== 'Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper'
        ) {
            return null;
        }

        $enumCodeExpr = $staticCall->args[0]->value ?? null;
        if (!$enumCodeExpr) {
            return null;
        }

        // Update getRepository(...) argument
        $getRepositoryCall->args[0]->value = $this->nodeFactory->createClassConstReference(
            'Oro\Bundle\EntityExtendBundle\Entity\EnumOption'
        );

        // Replace ->findAll() to ->findBy(['enumCode' => ...])
        return new MethodCall(
            $getRepositoryCall,
            new Identifier('findBy'),
            [
                $this->nodeFactory->createArg(
                    new Array_([
                        new ArrayItem(
                            $enumCodeExpr,
                            new String_('enumCode')
                        )
                    ])
                )
            ]
        );
    }
}
