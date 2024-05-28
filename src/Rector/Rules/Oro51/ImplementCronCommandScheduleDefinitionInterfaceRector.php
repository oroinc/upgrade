<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro51;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use Rector\Rector\AbstractScopeAwareRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ImplementCronCommandScheduleDefinitionInterfaceRector extends AbstractScopeAwareRector
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

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add `CronCommandScheduleDefinitionInterface` interface to Command::class implementations with `getDefaultDefinition()` method',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    class SomeClass extends \Symfony\Component\Console\Command\Command
                    {
                        public function getDefaultDefinition()
                        {
                            return '* * * * * ? *';
                        }
                    }
                    CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
                    class SomeClass extends \Symfony\Component\Console\Command\Command implements \Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface
                    {
                        public function getDefaultDefinition(): string
                        {
                            return '* * * * * ? *';
                        }
                    }
                    CODE_SAMPLE
                )
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactorWithScope(Node $node, Scope $scope): ?Node
    {
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
