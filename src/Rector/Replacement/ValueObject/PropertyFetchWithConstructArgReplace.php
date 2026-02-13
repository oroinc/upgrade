<?php

namespace Oro\UpgradeToolkit\Rector\Replacement\ValueObject;

use Webmozart\Assert\Assert;

/**
 * Value object describing which property fetches to replace with constructor arguments
 */
final class PropertyFetchWithConstructArgReplace
{
    public function __construct(
        private readonly string $class,
        private readonly ?array $properties = null
    ) {
        Assert::stringNotEmpty($class, 'Class name cannot be empty');

        if ($this->properties !== null) {
            Assert::allStringNotEmpty($this->properties, 'Property names cannot be empty');
        }
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function hasExplicitProperties(): bool
    {
        return $this->properties !== null;
    }
}
