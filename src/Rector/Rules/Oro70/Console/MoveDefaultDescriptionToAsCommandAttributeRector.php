<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\Console;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use Rector\Rector\AbstractRector;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Move $defaultDescription property value into existing #[AsCommand] attribute's description argument.
 *
 * Example:
 * - Before: #[AsCommand(name: 'app:my-command')]
 *           class MyCommand extends Command {
 *               protected static $defaultDescription = 'My description';
 *           }
 *
 * - After:  #[AsCommand(name: 'app:my-command', description: 'My description')]
 *           class MyCommand extends Command {
 *           }
 */
final class MoveDefaultDescriptionToAsCommandAttributeRector extends AbstractRector
{
    private const AS_COMMAND_CLASS = AsCommand::class;

    private const AS_COMMAND_SHORT = 'AsCommand';

    #[\Override]
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        $attribute = $this->findAsCommandAttribute($node);
        if (!$attribute instanceof Attribute) {
            return null;
        }

        if ($this->hasDescriptionArg($attribute)) {
            return null;
        }

        $property = $this->findDefaultDescriptionProperty($node);
        if (!$property instanceof Property) {
            return null;
        }

        $description = $this->extractStringValue($property);
        if ($description === null) {
            return null;
        }

        $attribute->args[] = new Arg(
            value: new String_($description),
            name: new Identifier('description')
        );

        $this->removeProperty($node, $property);

        return $node;
    }

    private function findAsCommandAttribute(Class_ $class): ?Attribute
    {
        foreach ($class->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (
                    $this->isName($attr, self::AS_COMMAND_CLASS)
                    || $this->isName($attr, self::AS_COMMAND_SHORT)
                ) {
                    return $attr;
                }
            }
        }

        return null;
    }

    private function hasDescriptionArg(Attribute $attribute): bool
    {
        foreach ($attribute->args as $arg) {
            if ($arg->name instanceof Identifier && $arg->name->name === 'description') {
                return true;
            }
        }

        return false;
    }

    private function findDefaultDescriptionProperty(Class_ $class): ?Property
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Property && $this->isName($stmt, 'defaultDescription')) {
                return $stmt;
            }
        }

        return null;
    }

    private function extractStringValue(Property $property): ?string
    {
        $propertyProperty = $property->props[0] ?? null;
        if ($propertyProperty === null) {
            return null;
        }

        if ($propertyProperty->default instanceof String_) {
            return $propertyProperty->default->value;
        }

        return null;
    }

    private function removeProperty(Class_ $class, Property $property): void
    {
        foreach ($class->stmts as $key => $stmt) {
            if ($stmt === $property) {
                unset($class->stmts[$key]);
                $class->stmts = array_values($class->stmts);

                return;
            }
        }
    }
}
