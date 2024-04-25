<?php

declare(strict_types=1);

namespace Oro\Rector\Rules\Oro51;

use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Rector\Application\DeletedFilesProcessor;
use Oro\Rector\NodeManipulator\ClassInsertManipulator;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassReflection;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Rector\Enum\ObjectReference;
use Rector\PhpParser\AstResolver;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PostRector\Collector\UseNodesToAddCollector;
use Rector\Rector\AbstractScopeAwareRector;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Oro\Tests\Rector\Rules\Oro51\ExtendedEntityUpdateRector\ExtendedEntityUpdateRectorTest
 */
class ExtendedEntityUpdateRector extends AbstractScopeAwareRector
{
    public function __construct(
        private ClassInsertManipulator $classInsertManipulator,
        private DeletedFilesProcessor $deletedFilesProcessor,
        private AstResolver $astResolver,
        private UseNodesToAddCollector $useNodesToAddCollector,
        private PhpDocInfoFactory $phpDocInfoFactory,
        private BetterNodeFinder $betterNodeFinder,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Updates extended entities to use new trait and interface instead of a model class extend',
            []
        );
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactorWithScope(Node $node, Scope $scope)
    {
        if (!$node->extends instanceof Name) {
            return null;
        }

        if (!\str_contains($node->extends->toString(), 'Bundle\Model\Extend')) {
            return null;
        }

        $parentClassFile = $scope->getClassReflection()->getParentClass()->getFileName();

        $node->extends = null;
        $this->moveModelExtendsToTheTop($scope, $node);

        $modelClassReflection = $scope->getClassReflection()->getParentClass();
        /** @var Class_ $modelClassNode */
        $modelClassNode = $this->astResolver->resolveClassFromClassReflection($modelClassReflection);

        $this->moveModelPhpDocsToTheTop($modelClassNode, $node);
        $this->moveModelUsesToTheTop($modelClassNode);
        $this->moveModelInterfacesAndTraitsToTheTop($modelClassNode, $node);
        // add trait and interface
        $this->classInsertManipulator->addAsFirstTrait($node, ExtendEntityTrait::class);
        $node->implements[] = new FullyQualified(ExtendEntityInterface::class);
        // delete model class
        $this->deletedFilesProcessor->addFileToDelete($parentClassFile);

        $this->updateCloneMethod($node);
        $this->removeParentConstructorCall($node);
    }

    /**
     *  move method and property Doc Blocks to the top
     */
    private function moveModelPhpDocsToTheTop(Class_ $modelClassNode, Node|Class_ $node): void
    {
        $modelClassPhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($modelClassNode);
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        foreach ($modelClassPhpDocInfo->getTagsByName('method') as $methodDocBlock) {
            $newDocBlock = clone $methodDocBlock;
            $newDocBlock->setAttribute(PhpDocAttributeKey::START_AND_END, null);
            // replace param and return types equal to the class name with self
            $methodParams = $methodDocBlock->value->parameters;
            foreach ($methodParams as $methodParam) {
                $methodParam->type = $this->updateSelfDocBlockType(
                    $methodParam->type,
                    $modelClassNode->name->toString()
                );
            }

            if ($methodDocBlock->value->returnType) {
                $methodDocBlock->value->returnType = $this->updateSelfDocBlockType(
                    $methodDocBlock->value->returnType,
                    $modelClassNode->name->toString()
                );
            }

            $phpDocInfo->addPhpDocTagNode($newDocBlock);
        }

        foreach ($modelClassPhpDocInfo->getTagsByName('property') as $propertyDocBlock) {
            $newDocBlock = clone $propertyDocBlock;
            $newDocBlock->setAttribute(PhpDocAttributeKey::START_AND_END, null);
            // replace param and return types equal to the class name with self
            $newDocBlock->value->type = $this->updateSelfDocBlockType(
                $newDocBlock->value->type,
                $modelClassNode->name->toString()
            );
            $phpDocInfo->addPhpDocTagNode($newDocBlock);
        }
    }

    private function updateSelfDocBlockType(TypeNode $type, string $modelClassName): TypeNode
    {
        if ($type instanceof IdentifierTypeNode && $type->name === $modelClassName) {
            return new IdentifierTypeNode(ObjectReference::SELF);
        }

        return $type;
    }

    private function moveModelUsesToTheTop(Class_ $modelClassNode): void
    {
        $uses = $this->betterNodeFinder->findInstanceOf(
            $modelClassNode,
            Use_::class
        );
        foreach ($uses as $use_) {
            foreach ($use_->uses as $use) {
                $this->useNodesToAddCollector->addUseImport(new FullyQualifiedObjectType($use->name->toString()));
            }
        }
    }

    private function moveModelInterfacesAndTraitsToTheTop(Class_ $modelClassNode, Class_ $node): void
    {
        foreach ($modelClassNode->implements as $modelClassImplement) {
            $node->implements[] = new FullyQualified($modelClassImplement->toString());
        }

        $modelClassTraitUses = $modelClassNode->getTraitUses();
        foreach ($modelClassTraitUses as $modelClassTraitUse) {
            foreach ($modelClassTraitUse->traits as $modelClassTrait) {
                $this->classInsertManipulator->addAsFirstTrait($node, $modelClassTrait->toString());
            }
        }
    }

    /**
     * If parent class has extends, move it to the top
     */
    private function moveModelExtendsToTheTop(Scope $scope, Class_ $node): void
    {
        $modelParentClass = $scope->getClassReflection()->getParentClass()->getParentClass();
        if ($modelParentClass instanceof ClassReflection) {
            $node->extends = new FullyQualified($modelParentClass->getName());
        }
    }

    private function updateCloneMethod(Class_ $node): void
    {
        $cloneMethod = $this->betterNodeFinder->findFirst($node->stmts, function (Node $node): bool {
            return $node instanceof ClassMethod && $this->isName($node->name, '__clone');
        });
        if (!$cloneMethod instanceof Node) {
            return;
        }

        $alreadyCalled = $this->betterNodeFinder->findFirst($cloneMethod->stmts, function (Node $node): bool {
            return $node instanceof MethodCall && $this->isName($node->name, 'cloneExtendEntityStorage');
        });
        if ($alreadyCalled instanceof Node) {
            return;
        }

        $staticCall = $this->nodeFactory->createLocalMethodCall('cloneExtendEntityStorage');
        $cloneMethod->stmts[] = new Expression($staticCall);
    }

    private function removeParentConstructorCall(Class_ $node): void
    {
        $constructor = $this->betterNodeFinder->findFirst($node->stmts, function (Node $node): bool {
            return $node instanceof ClassMethod && $this->isName($node->name, '__construct');
        });
        if (!$constructor instanceof Node) {
            return;
        }

        $parentConstructorCall = $this->betterNodeFinder->findFirst($constructor->stmts, function (Node $node): bool {
            return $node instanceof StaticCall &&
                $this->isName($node->class, ObjectReference::PARENT) &&
                $this->isName($node->name, '__construct');
        });
        if (!$parentConstructorCall instanceof Node) {
            return;
        }
        // Remove parent constructor call
        foreach ($constructor->stmts as $key => $stmt) {
            if ($stmt->expr === $parentConstructorCall) {
                unset($constructor->stmts[$key]);
            }
        }
    }
}
