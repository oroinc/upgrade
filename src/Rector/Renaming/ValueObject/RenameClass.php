<?php

declare (strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Renaming\ValueObject;

use Rector\Validation\RectorAssert;

/**
 * Value object representing a class renaming rule configuration.
 * Stores the old class name, the new class name, and the list of targets to apply the renaming to.
 */
final class RenameClass
{
    public function __construct(
        private string $oldClass,
        private string $newClass,
        private array $applyTo,
    ) {
        RectorAssert::className($oldClass);
        RectorAssert::className($newClass);

        array_walk($applyTo, [RectorAssert::class, 'className']);
    }

    public function getApplyTo(): array
    {
        return $this->applyTo;
    }

    public function getConfiguration(): array
    {
        return [$this->oldClass => $this->newClass];
    }

    public function getNewClass(): string
    {
        return $this->newClass;
    }

    public function getOldClass(): string
    {
        return $this->oldClass;
    }
}
