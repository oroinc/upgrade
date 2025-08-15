<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Renaming\ValueObject;

/**
 * Value object representing an annotation tag renaming rule configuration.
 * Stores the old tag name, the new tag name.
 */
final class RenameAnnotationTag
{
    public function __construct(
        private readonly string $oldTag,
        private readonly string $newTag,
    ) {
    }

    public function getOldTag(): string
    {
        return $this->oldTag;
    }

    public function getNewTag(): string
    {
        return $this->newTag;
    }
}
