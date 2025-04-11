<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro51;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;

final class ImplementCronCommandScheduleDefinitionInterfaceRector extends AbstractRector
{
    /**
     * @var string
     */
    private const INTERFACE = 'Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface';

    /**
     * @var string
     */
    private const COMMAND_CLASS = 'Symfony\Component\Console\Command\Command';

    /**
     * @var string
     */
    private const METHOD_NAME = 'getDefaultDefinition';

    /**
     * @return array<class-string<Node>>
     */
    #[\Override]
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    #[\Override]
    public function refactor(Node $node): ?Node
    {
        $scope = ScopeFetcher::fetch($node);
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }

        $method = $node->getMethod(self::METHOD_NAME);
        if (!$method instanceof ClassMethod) {
            return null;
        }

        if (!$classReflection->isSubclassOf(self::COMMAND_CLASS)) {
            return null;
        }

        if ($classReflection->implementsInterface(self::INTERFACE)) {
            return null;
        }

        // add interface
        $node->implements[] = new FullyQualified(self::INTERFACE);
        // add return type
        if ($method->returnType === null) {
            $method->returnType = new Identifier('string');
        }

        return $node;
    }
}
