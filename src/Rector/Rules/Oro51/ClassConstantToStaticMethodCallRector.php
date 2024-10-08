<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro51;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractScopeAwareRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class ClassConstantToStaticMethodCallRector extends AbstractScopeAwareRector implements ConfigurableRectorInterface
{
    private array $oldToNewConstants = [];

    #[\Override]
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace class constant by static method call',
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

    #[\Override]
    public function getNodeTypes(): array
    {
        return [ClassConstFetch::class];
    }

    #[\Override]
    public function refactorWithScope(Node $node, Scope $scope)
    {
        foreach ($this->oldToNewConstants as $oldConstant => $newClassWithMethod) {
            [$oldConstantClass, $oldConstantName] = explode('::', $oldConstant);
            [$newClass, $newMethod] = explode('::', $newClassWithMethod);

            if (!$this->isName($node->name, $oldConstantName)) {
                continue;
            }

            if (!$this->isObjectType($node->class, new ObjectType($oldConstantClass))) {
                continue;
            }

            if (!\class_exists($newClass)) {
                throw new \RuntimeException(sprintf('Class "%s" does not exist', $newClass));
            }

            if (!\method_exists($newClass, $newMethod)) {
                throw new \RuntimeException(sprintf('Method "%s" does not exist in class "%s"', $newMethod, $newClass));
            }

            return $this->nodeFactory->createStaticCall($newClass, $newMethod);
        }

        return null;
    }

    #[\Override]
    public function configure(array $configuration): void
    {
        $this->oldToNewConstants = $configuration;
    }
}
