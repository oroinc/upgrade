<?php

declare (strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Renaming\Attribute;

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\RenameAttributeNamedArg;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Exception\ShouldNotHappenException;
use Rector\NodeAnalyzer\ArgsAnalyzer;
use Rector\Rector\AbstractRector;
use RectorPrefix202507\Webmozart\Assert\Assert;

/**
 * Renames named arguments in PHP attributes using a configurable mapping.
 *
 * Transforms attribute named arguments from old names to new names while preserving
 * the attribute class name format (short name or fully qualified name).
 *
 * Example:
 * - Before: #[Acl(id: 'test', group_name: 'commerce')]
 * - After:  #[Acl(id: 'test', groupName: 'commerce')]
 *
 * If the attribute class name doesn't match the configured class, it will be updated
 * to use the configured tag name.
 */
final class AttributeNamedArgRenameRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $renameAttributeNamedArgs = [];
    private bool $hasChanged = false;

    public function __construct(
        private readonly ArgsAnalyzer $argsAnalyzer
    ) {
    }

    #[\Override]
    public function getNodeTypes(): array
    {
        return [Attribute::class];
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        if (!$this->renameAttributeNamedArgs) {
            throw new ShouldNotHappenException(
                \sprintf('%s::renameAttributeNamedArgs property value cannot be empty', $this::class)
            );
        }

        $this->hasChanged = false;
        foreach ($this->renameAttributeNamedArgs as $renameAttributeNamedArg) {
            if ($this->isApplicable($node, $renameAttributeNamedArg)) {
                $this->rename($node, $renameAttributeNamedArg);
            }
        }

        return $this->hasChanged ? $node : null;
    }

    #[\Override]
    public function configure(array $configuration): void
    {
        Assert::allIsAOf($configuration, RenameAttributeNamedArg::class);
        $this->renameAttributeNamedArgs = $configuration;
    }

    private function isApplicable(Node $node, RenameAttributeNamedArg $renameAttributeNamedArg): bool
    {
        return ($renameAttributeNamedArg->getAttributeClass() === $node->name->name
                || $renameAttributeNamedArg->getTag() === $node->name?->getLast())
            && $this->argsAnalyzer->hasNamedArg($node->args);
    }

    private function rename(Node $node, RenameAttributeNamedArg $renameAttributeNamedArg): void
    {
        $args = $node->args;
        foreach ($args as $arg) {
            if ($renameAttributeNamedArg->getOldArgName() === $arg->name?->name) {
                $arg->name = new Identifier($renameAttributeNamedArg->getNewArgName());

                if ($renameAttributeNamedArg->getAttributeClass() !== $node->name->name) {
                    $node->name = new Name($renameAttributeNamedArg->getTag());
                }

                $this->hasChanged = true;
            }
        }
    }
}
