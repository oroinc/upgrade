<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro51;

use Oro\UpgradeToolkit\Rector\TopicClass\TopicClassNameGenerator;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;

class TopicClassConstantUsageToTopicNameRector extends AbstractRector
{
    #[\Override]
    public function getNodeTypes(): array
    {
        return [ClassConstFetch::class];
    }

    #[\Override]
    public function refactor(Node $node)
    {
        $classExpr = $node->class;

        // \SomeClass::CONST
        if ($classExpr instanceof Node\Name) {
            $type = $classExpr->toString();
        }
        // $var::CONST
        elseif ($classExpr instanceof Variable) {
            $type = $this->nodeTypeResolver->getType($classExpr);

            if ($type instanceof ObjectType) {
                $type = $type->getClassName();
            } else {
                return null;
            }
        } else {
            return null;
        }

        if (!\str_ends_with($type, '\\Async\\Topics')) {
            return null;
        }

        $constantName = $node->name->name;
        $topicClass = $this->getTopicClass($node, $constantName);

        if (!\class_exists($topicClass)) {
            return null;
        }

        if (!\method_exists($topicClass, 'getName')) {
            return null;
        }

        return $this->nodeFactory->createStaticCall($topicClass, 'getName');
    }

    private function getTopicClass(Node $node, string $constantName): string
    {
        $namespace = $node->class->slice(0, -1)->toString() . '\\Topic\\';

        return $namespace . TopicClassNameGenerator::getByConstantName($constantName);
    }
}
