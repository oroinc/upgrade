<?php

namespace Oro\UpgradeToolkit\Rector\Replacement\ValueObject;

use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\Contract\ArgumentReplacementInterface;

/**
 * Describes a single replacement of an attribute argument value.
 *
 * Configuration value object for
 * @see \Oro\UpgradeToolkit\Rector\Rules\Replace\ReplaceAttributeAgrRector
 */
final class AttributeArgReplace implements ArgumentReplacementInterface
{
    /**
     * @param string $tag Target attribute name/alias to use for matching the attribute.
     * @param string $class Fully-qualified attribute class name
     * @param string $argName The constructor argument/parameter name
     * @param mixed $oldValue Current value that must match (supports scalars/null/arrays and constant-like Expr)
     * @param mixed $newValue Replacement value (supports scalars/null/arrays and constant-like Expr)
     * @param string $method Method name to match. For attributes this is typically "__construct".
     */
    public function __construct(
        private readonly string $tag,
        private readonly string $class,
        private readonly string $argName,
        private readonly mixed $oldValue,
        private readonly mixed $newValue,
        private readonly string $method = '__construct',
    ) {
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getNewValue(): mixed
    {
        return $this->newValue;
    }

    public function getOldValue(): mixed
    {
        return $this->oldValue;
    }

    public function getArgName(): string
    {
        return $this->argName;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
