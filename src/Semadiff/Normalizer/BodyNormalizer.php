<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Normalizer;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\NodeVisitorAbstract;

/**
 * Strips cosmetic elements from AST nodes while preserving semantics.
 * Used as a NodeVisitor during AST traversal.
 */
final class BodyNormalizer extends NodeVisitorAbstract
{
    public function enterNode(Node $node): ?int
    {
        // Strip all comments
        $node->setAttribute('comments', []);

        // Strip #[\Override] attributes (PHP 8.3 marker, no runtime effect)
        if (property_exists($node, 'attrGroups') && is_array($node->attrGroups)) {
            /** @var AttributeGroup[] $attrGroups */
            $attrGroups = $node->attrGroups;
            $node->attrGroups = $this->filterOverrideAttributes($attrGroups);
        }

        return null;
    }

    /**
     * @param AttributeGroup[] $attrGroups
     * @return AttributeGroup[]
     */
    private function filterOverrideAttributes(array $attrGroups): array
    {
        $filtered = [];
        foreach ($attrGroups as $group) {
            $attrs = array_filter($group->attrs, function ($attr) {
                $name = $attr->name->toString();
                // Remove Override attribute (with or without leading backslash)
                return !in_array(ltrim($name, '\\'), ['Override'], true);
            });

            if ($attrs !== []) {
                $group->attrs = array_values($attrs);
                $filtered[] = $group;
            }
        }

        return $filtered;
    }
}
