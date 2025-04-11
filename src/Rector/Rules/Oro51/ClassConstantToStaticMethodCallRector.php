<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro51;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PHPStan\Type\ObjectType;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;

class ClassConstantToStaticMethodCallRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $oldToNewConstants = [];

    #[\Override]
    public function getNodeTypes(): array
    {
        return [ClassConstFetch::class];
    }

    #[\Override]
    public function refactor(Node $node)
    {
        $scope = ScopeFetcher::fetch($node);
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
