<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\Doctrine;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeAbstract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use Rector\NodeAnalyzer\ArgsAnalyzer;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;

/**
 * Add type hint to setParameter when entity object is passed
 * Handles both variables and method calls that return entities
 *
 * Examples:
 * - Before: ->setParameter('product', $product)
 *   After:  ->setParameter('product', $product->getId(), Types::INTEGER)
 *
 * - Before: ->setParameter('product', $order->getLineItem()->getProduct())
 *   After:  ->setParameter('product', $order->getLineItem()->getProduct()?->getId(), Types::INTEGER)
 */
final class AddTypeToSetParameterRector extends AbstractRector
{
    private const CLASS_NAME = 'Doctrine\\ORM\\QueryBuilder';
    private const METHOD_NAME = 'setParameter';
    private const ARGUMENT_NAME = 'value';
    private const ENTITY_REPOSITORY = 'Doctrine\\ORM\\EntityRepository';
    private const ENTITY_ATTRIBUTE = 'Doctrine\\ORM\\Mapping\\Entity';

    public function __construct(
        private readonly ReflectionResolver $reflectionResolver,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ArgsAnalyzer $argsAnalyzer,
    ) {
    }

    #[\Override]
    public function getNodeTypes(): array
    {
        return [
            MethodCall::class,
            NullsafeMethodCall::class,
        ];
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;

        if ($this->shouldSkip($node)) {
            return null;
        }

        // Check if already has 3 or more arguments (type already added)
        if (3 <= count($node->args)) {
            return null;
        }

        $argPosition = $this->getAgrPosition($node);
        if (null === $argPosition) {
            return null;
        }

        $value = $node->args[$argPosition]->value;
        if (!$this->isDoctrineEntity($value)) {
            return null;
        }

        if ($value instanceof Node\Expr\Variable) {
            $hasChanged = $this->refactorVariable($node, $argPosition);
        }

        if ($value instanceof Node\Expr\MethodCall) {
            $hasChanged = $this->refactorMethodCall($node, $argPosition);
        }

        return $hasChanged ? $node : null;
    }

    private function shouldSkip(Node $node): bool
    {
        $nodeName = $this->getName($node->name);
        if (null === $nodeName) {
            return true;
        }

        $scope = ScopeFetcher::fetch($node);
        $repositoryClassReflection = $this->reflectionProvider->getClass(self::ENTITY_REPOSITORY);
        if (!$scope->getClassReflection()?->isSubclassOfClass($repositoryClassReflection)) {
            return true;
        }

        if (!$this->nodeNameResolver->isStringName($nodeName, self::METHOD_NAME)) {
            return true;
        }

        $objectType = new ObjectType(self::CLASS_NAME);
        if (!$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($node, $objectType)) {
            return true;
        }

        if ($this->shouldSkipClassMethod($node)) {
            return true;
        }

        return false;
    }

    private function shouldSkipClassMethod(MethodCall|NullsafeMethodCall $call): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflectionSourceObject($call);
        if (!$classReflection instanceof ClassReflection) {
            return false;
        }

        if (!$this->reflectionProvider->hasClass(self::CLASS_NAME)) {
            return false;
        }

        $targetClassReflection = $this->reflectionProvider->getClass(self::CLASS_NAME);
        if ($classReflection->getName() === $targetClassReflection->getName()) {
            return false;
        }

        if (!$classReflection->hasMethod(self::METHOD_NAME)) {
            return false;
        }

        return $classReflection->hasMethod(self::METHOD_NAME);
    }

    private function isDoctrineEntity(NodeAbstract $value): bool
    {
        $objectClassReflections = ScopeFetcher::fetch($value)
            ->getType($value)
            ->getObjectClassReflections();

        if (empty($objectClassReflections) || 1 !== count($objectClassReflections)) {
            return false;
        }

        $className = $objectClassReflections[0]->getName();
        $reflection = new \ReflectionClass($className);
        $attributes = $reflection->getAttributes(self::ENTITY_ATTRIBUTE);

        if (!empty($attributes)) {
            return true;
        }

        return str_contains($className, '\\Entity\\');
    }

    private function getAgrPosition(Node $node): ?int
    {
        // Default position
        $position = 1;
        if ($this->argsAnalyzer->hasNamedArg($node->args)) {
            foreach ($node->args as $index => $arg) {
                if (self::ARGUMENT_NAME === $this->nodeNameResolver->getName($arg)) {
                    $position = $index;
                }
            }
        }

        return isset($node->args[$position]) ? $position : null;
    }

    /**
     * Transforms argument like $entity to $entity->getId()
     * and adds Types::INTEGER as third argument
     */
    private function refactorVariable(Node $node, int $position): bool
    {
        $arg = $node->args[$position];
        $arg->value = new MethodCall($arg->value, 'getId');
        $node->args[] = $this->createTypesArg($node);

        return true;
    }

    /**
     * Transforms argument like $obj->getEntity() to $obj->getEntity()?->getId()
     * and adds Types::INTEGER as third argument
     */
    private function refactorMethodCall(Node $node, int $position): bool
    {
        $arg = $node->args[$position];
        $arg->value = new NullsafeMethodCall($arg->value, 'getId');
        $node->args[] = $this->createTypesArg($node);

        return true;
    }

    private function createTypesArg(Node $node): Arg
    {
        $arg = new Arg(
            new ClassConstFetch(
                new FullyQualified('Doctrine\\DBAL\\Types\\Types'),
                'INTEGER'
            )
        );

        if ($this->argsAnalyzer->hasNamedArg($node->args)) {
            $arg->name = new Node\Identifier('type');
        }

        return $arg;
    }
}
