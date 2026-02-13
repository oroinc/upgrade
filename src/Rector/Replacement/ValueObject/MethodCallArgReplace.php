<?php

namespace Oro\UpgradeToolkit\Rector\Replacement\ValueObject;

use Oro\UpgradeToolkit\Rector\Replacement\ValueObject\Contract\ArgumentReplacementInterface;
use PHPStan\Type\ObjectType;
use Rector\Validation\RectorAssert;

/**
 * Configuration value object for
 * @see \Oro\UpgradeToolkit\Rector\Rules\Replace\ReplaceArgInMethodCallRector
 */
final class MethodCallArgReplace implements ArgumentReplacementInterface
{
    /**
     * @param string $class Fully-qualified class name the call is expected to be made on
     * @param string $method Method name to match
     * @param string $argName Argument/parameter name to replace
     * @param mixed $oldValue Current value that must match (supports scalars/null/arrays and constant-like Expr)
     * @param mixed $newValue Replacement value (supports scalars/null/arrays and constant-like Expr)
     */
    public function __construct(
        private readonly string $class,
        private readonly string $method,
        private readonly string $argName,
        private readonly mixed $oldValue = null,
        private readonly mixed $newValue = null,
    ) {
        RectorAssert::className($class);
        RectorAssert::methodName($method);
    }

    public function getClass(): string
    {
        return $this->class;
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

    public function getObjectType(): ObjectType
    {
        return new ObjectType($this->class);
    }
}
