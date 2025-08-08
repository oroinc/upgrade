<?php

namespace Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;

/**
 * Rector rule that replaces the usage of ExtendExtensionAwareTrait with OutdatedExtendExtensionAwareTrait
 * in migration classes implementing Oro\Bundle\MigrationBundle\Migration\Migration.
 * Also removes related properties and methods.
 */
final class ReplaceExtendExtensionAwareTraitRector extends AbstractRector
{
    private const MIGRATION_INTERFACE = 'Oro\\Bundle\\MigrationBundle\\Migration\\Migration';

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node->isAbstract()) {
            return null;
        }

        if (!$node->hasAttribute(AttributeKey::SCOPE)) {
            return null;
        }

        $scope = ScopeFetcher::fetch($node);
        $classReflection = $scope->getClassReflection();
        if (!$classReflection) {
            return null;
        }

        $isImplementInterface = false;
        foreach ($classReflection->getImmediateInterfaces() as $interface) {
            if (self::MIGRATION_INTERFACE === $interface->getName()) {
                $isImplementInterface = true;
            }
        }

        if (!$isImplementInterface) {
            return null;
        }

        // Remove ExtendExtensionAwareTrait and related members
        // Then add OutdatedExtendExtensionAwareTrait if needed
        if ($this->unsetExtendExtensionAwareTrait($node)) {
            $traitUse = new TraitUse([new FullyQualified(OutdatedExtendExtensionAwareTrait::class)]);
            $node->stmts = \array_merge([$traitUse], $node->stmts);

            return $node;
        }

        return null;
    }

    /**
     * Removes ExtendExtensionAwareTrait usage, related property, and setter method from the class.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function unsetExtendExtensionAwareTrait(Node $node): bool
    {
        $isStmtChanged = false;
        foreach ($node->stmts as $key => $stmt) {
            if ($stmt instanceof TraitUse) {
                foreach ($stmt->traits as $pos => $trait) {
                    if (ExtendExtensionAwareTrait::class === $trait->name) {
                        unset($node->stmts[$key]->traits[$pos]);
                        if (0 === count($node->stmts[$key]->traits)) {
                            unset($node->stmts[$key]);
                        }
                        $isStmtChanged = true;
                    }
                }
            }

            if ($stmt instanceof Property) {
                // Remove private property $extendExtension
                if ($stmt->isPrivate() && $this->shouldRemoveProperty($stmt)) {
                    unset($node->stmts[$key]);
                    $isStmtChanged = true;
                }
            }

            if ($stmt instanceof ClassMethod) {
                // Remove setExtendExtension method
                if ('setExtendExtension' === $stmt->name->name) {
                    unset($node->stmts[$key]);
                    $isStmtChanged = true;
                }
            }
        }

        return $isStmtChanged;
    }

    private function shouldRemoveProperty(Property $property): bool
    {
        foreach ($property->props as $prop) {
            // Regular case: private ExtendExtension $extendExtension;
            if ($prop instanceof PropertyItem) {
                if ('extendExtension' === $prop->name->name) {
                    return true;
                }
                // Edge case: private ExtendExtension $outdatedExtendExtension;
                if ('outdatedExtendExtension' === $prop->name->name
                    && $property->type instanceof FullyQualified
                    && ExtendExtension::class === $property->type->toString()
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
