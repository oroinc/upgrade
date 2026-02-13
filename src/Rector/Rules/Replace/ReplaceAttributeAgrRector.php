<?php

namespace Oro\UpgradeToolkit\Rector\Rules\Replace;

use Oro\UpgradeToolkit\Rector\Replacement\ArgumentReplaceHelper;
use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\AttributeArgReplace;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Name;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Webmozart\Assert\Assert;

/**
 * Replaces a configured attribute argument value (old → new) for PHP 8 attributes.
 * It matches attributes and then replaces a single argument when its value equals the configured "old" value.
 *
 * Before:
 * #[Route(name: 'old')]
 * After:
 * #[Route(name: 'new')]
 */
class ReplaceAttributeAgrRector extends AbstractRector implements ConfigurableRectorInterface
{
    private array $configuration = [];

    public function __construct(
        private readonly ArgumentReplaceHelper $argumentReplaceHelper,
    ) {
    }

    #[\Override]
    public function configure(array $configuration): void
    {
        Assert::allIsInstanceOf($configuration, AttributeArgReplace::class);
        $this->configuration = $configuration;
    }

    public function getNodeTypes(): array
    {
        return [Attribute::class];
    }

    public function refactor(Node $node): ?Node
    {
        $hasChanged = false;

        foreach ($this->configuration as $attributeArgReplace) {
            if (
                !$this->isName($node, $attributeArgReplace->getClass())
                && !$this->isName($node, $attributeArgReplace->getTag())
            ) {
                continue;
            }

            $changedNode = $this->argumentReplaceHelper->replace($node, $attributeArgReplace);
            if (null === $changedNode) {
                continue;
            }

            $hasChanged = true;
            $node = $changedNode;

            if ($attributeArgReplace->getClass() !== $this->getName($node)) {
                $node->name = new Name($attributeArgReplace->getTag());
            }
        }

        return $hasChanged ? $node : null;
    }
}
