<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector\Fixture;

class SkipNonReflectionFixture
{
    public function testSkipNonReflectionCalls()
    {
        // This should NOT be changed - not a Reflection class
        $customObject = new CustomClass();
        $customObject->setAccessible(true);

        // This should NOT be changed - different method
        $reflectionProperty = new \ReflectionProperty('Class', 'prop');
        $reflectionProperty->setValue($object, 'value');
        $reflectionProperty->getValue($object);

        // This should NOT be changed - ReflectionClass
        $reflectionClass = new \ReflectionClass('SomeClass');
        $reflectionClass->setAccessible(true);
    }
}

class CustomClass
{
    public function setAccessible($value)
    {
        // Some custom implementation
    }
}
