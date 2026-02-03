<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector\Fixture;

class MixedReflectionFixture
{
    public function testMixedReflections()
    {
        $object = new \stdClass();

        // ReflectionProperty
        $reflectionProperty = new \ReflectionProperty($object, 'property');
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($object);

        // ReflectionMethod
        $reflectionMethod = new \ReflectionMethod($object, 'method');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($object);

        // Multiple calls
        $prop1 = new \ReflectionProperty('Class1', 'prop1');
        $prop1->setAccessible(true);

        $method1 = new \ReflectionMethod('Class2', 'method1');
        $method1->setAccessible(false);
    }
}

-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector\Fixture;

class MixedReflectionFixture
{
    public function testMixedReflections()
    {
        $object = new \stdClass();

        // ReflectionProperty
        $reflectionProperty = new \ReflectionProperty($object, 'property');
        $value = $reflectionProperty->getValue($object);

        // ReflectionMethod
        $reflectionMethod = new \ReflectionMethod($object, 'method');
        $reflectionMethod->invoke($object);

        // Multiple calls
        $prop1 = new \ReflectionProperty('Class1', 'prop1');

        $method1 = new \ReflectionMethod('Class2', 'method1');
    }
}
