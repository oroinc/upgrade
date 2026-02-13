<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Renaming\ValueObject;

use PHPStan\Type\ObjectType;
use Rector\Renaming\Contract\MethodCallRenameInterface;
use Rector\Validation\RectorAssert;

/**
 * Configuration for a method call rename rules
 * Defines target class and old/new method names, with optional chained methods
 */
final class MethodCallReplace implements MethodCallRenameInterface
{
    public function __construct(
        private readonly string $class,
        private readonly string $oldMethod,
        private readonly string $newMethod,
        private readonly array $chainedMethods,
    ) {
        RectorAssert::className($class);
        RectorAssert::methodName($oldMethod);
        RectorAssert::methodName($newMethod);
        foreach ($chainedMethods as $chainedMethod) {
            RectorAssert::methodName($chainedMethod);
        }
    }

    public function getClass() : string
    {
        return $this->class;
    }

    public function getObjectType() : ObjectType
    {
        return new ObjectType($this->class);
    }

    public function getOldMethod() : string
    {
        return $this->oldMethod;
    }

    public function getNewMethod() : string
    {
        return $this->newMethod;
    }

    public function getChainedMethods(): array
    {
        return $this->chainedMethods;
    }
}
