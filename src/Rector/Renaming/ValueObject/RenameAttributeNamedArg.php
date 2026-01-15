<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Renaming\ValueObject;

/**
 * Value object representing an attribute named argument renaming rule configuration.
 *
 * Stores the attribute class name, tag (short name),
 * and the mapping from old argument name to new argument name.
 */
final class RenameAttributeNamedArg
{
    public function __construct(
        private readonly string $tag,
        private readonly string $attributeClass,
        private readonly string $oldArgName,
        private readonly string $newArgName,
    ) {
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getAttributeClass(): string
    {
        return $this->attributeClass;
    }

    public function getOldArgName(): string
    {
        return $this->oldArgName;
    }

    public function getNewArgName(): string
    {
        return $this->newArgName;
    }
}
