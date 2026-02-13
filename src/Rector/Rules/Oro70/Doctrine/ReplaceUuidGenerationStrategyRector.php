<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\Doctrine;

use Oro\UpgradeToolkit\Rector\Replacement\ArgumentReplaceHelper;
use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\AttributeArgReplace;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Property;
use Rector\Rector\AbstractRector;

/**
 * Replace UUID generation strategy with CUSTOM and add CustomIdGenerator attribute
 *
 * Example:
 * - Before: #[ORM\GeneratedValue(strategy: 'UUID')]
 *
 * - After:  #[ORM\GeneratedValue(strategy: 'CUSTOM')]
 *           #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
 */
final class ReplaceUuidGenerationStrategyRector extends AbstractRector
{
    private AttributeArgReplace $attributeArgReplace;
    private bool $hasChanged = false;

    public function __construct(
        private readonly ArgumentReplaceHelper $argumentReplaceHelper,
    ) {
        $this->attributeArgReplace = new AttributeArgReplace(
            tag: 'ORM\GeneratedValue',
            class: 'Doctrine\\ORM\\Mapping\\GeneratedValue',
            argName: 'strategy',
            oldValue: 'UUID',
            newValue: 'CUSTOM',
        );
    }

    public function getNodeTypes(): array
    {
        return [Property::class];
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        $this->hasChanged = false;

        foreach ($node->attrGroups as $position => $attrGroup) {
            foreach ($attrGroup->attrs as $index => $attr) {
                if ($this->shouldReplace($attr)) {
                    $this->replaceUuidGenerationStrategy($node, $attrGroup, $attr, $index, $position);
                }
            }
        }

        return $this->hasChanged ? $node : null;
    }

    private function shouldReplace(Attribute $attr): bool
    {
        return $this->isName($attr, $this->attributeArgReplace->getClass())
            || $this->isName($attr, $this->attributeArgReplace->getTag());
    }

    private function replaceUuidGenerationStrategy(
        Node $node,
        AttributeGroup $attrGroup,
        Attribute $attr,
        int $index,
        int $position
    ): void {
        $result = $this->argumentReplaceHelper->replace($attr, $this->attributeArgReplace);

        if ($result instanceof Attribute) {
            $this->updateAttributeName($attr, $result, $attrGroup, $index);
            $this->addCustomIdGeneratorAttribute($node, $position);
            $this->hasChanged = true;
        }
    }

    private function updateAttributeName(
        Attribute $attr,
        Attribute $result,
        AttributeGroup $attrGroup,
        int $index
    ): void {
        if ($this->attributeArgReplace->getClass() !== $this->getName($attr)) {
            $result->name = new Name($this->attributeArgReplace->getTag());
            $attrGroup->attrs[$index] = $result;
        }
    }

    private function addCustomIdGeneratorAttribute(Node $node, int $position): void
    {
        $customIdGenAttr = new Attribute(
            new Name('\\Doctrine\\ORM\\Mapping\\CustomIdGenerator'),
            [
                new Arg(
                    value: new Node\Expr\ClassConstFetch(
                        new Name('\\Oro\\Component\\DoctrineUtils\\ORM\\Id\\UuidGenerator'),
                        new Identifier('class')
                    ),
                    name: new Identifier('class')
                )
            ]
        );

        $attrGroups = $node->attrGroups;
        array_splice($attrGroups, $position + 1, 0, [new AttributeGroup([$customIdGenAttr])]);
        $node->attrGroups = array_values($attrGroups);
    }
}
