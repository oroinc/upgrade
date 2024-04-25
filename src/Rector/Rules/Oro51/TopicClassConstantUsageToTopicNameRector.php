<?php

declare(strict_types=1);

namespace Oro\Rector\Rules\Oro51;

use Oro\Rector\TopicClass\TopicClassNameGenerator;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PHPStan\Analyser\Scope;
use Rector\Rector\AbstractScopeAwareRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class TopicClassConstantUsageToTopicNameRector extends AbstractScopeAwareRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace class Topics constant reference with *Topic::getName() call',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    $this->send(\Acme\Bundle\DemoBundle\Async\Topics::SEND_EMAIL, []);
                    CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                    $this->send(\Acme\Bundle\DemoBundle\Async\Topic\SendEmailTopic::getName(), []);
                    CODE_SAMPLE
                )
            ]
        );
    }

    public function getNodeTypes(): array
    {
        return [ClassConstFetch::class];
    }

    public function refactorWithScope(Node $node, Scope $scope)
    {
        $type = $node->class->toString();
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
