<?php

declare(strict_types=1);

namespace Oro\Rector;

use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Rector\Renaming\ValueObject\RenameStaticMethod;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RenameValueNormalizerUtilMethodIfTrowsExceptionRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Turns method names to new ones.',
            [
                new CodeSample(
                    '
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType, true);
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType, false);
                    
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType);
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType, true);
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType, false);
                    ',
                    '
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::tryConvertToEntityType($valueNormalizer, $entityType);
                    
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
                    Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::tryConvertToEntityClass($valueNormalizer, $entityType);
                    ',
                )
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [StaticCall::class];
    }

    /**
     * @param StaticCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isObjectType($node->class, new ObjectType(ValueNormalizerUtil::class))) {
            return null;
        }

        if (!$this->isName($node->name, 'convertToEntityType') && !$this->isName($node->name, 'convertToEntityClass')) {
            return null;
        }

        if (count($node->args) !== 3) {
            return null;
        }

        $thirdArgument = $node->args[2]->value;
        if (!$thirdArgument instanceof ConstFetch) {
            return null;
        }

        if ($this->isName($thirdArgument->name, 'true')) {
            // remove third argument
            unset($node->args[2]);

            return $node;
        }

        if (!$this->isName($thirdArgument->name, 'false')) {
            return null;
        }

        if ($this->isName($node->name, 'convertToEntityType')) {
            $staticMethodRename = new RenameStaticMethod(
                ValueNormalizerUtil::class,
                'convertToEntityType',
                ValueNormalizerUtil::class,
                'tryConvertToEntityType'
            );
        } else {
            $staticMethodRename = new RenameStaticMethod(
                ValueNormalizerUtil::class,
                'convertToEntityClass',
                ValueNormalizerUtil::class,
                'tryConvertToEntityClass'
            );
        }

        // remove third argument
        unset($node->args[2]);

        return $this->rename($node, $staticMethodRename);
    }

    private function rename(StaticCall $staticCall, RenameStaticMethod $renameStaticMethod): StaticCall
    {
        $staticCall->name = new Identifier($renameStaticMethod->getNewMethod());
        if ($renameStaticMethod->hasClassChanged()) {
            $staticCall->class = new FullyQualified($renameStaticMethod->getNewClass());
        }

        return $staticCall;
    }
}
