<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector\Fixture;

class ReflectionMethodFixture
{
    public function testReflectionMethod()
    {
        $object = new \stdClass();
        $reflectionMethod = new \ReflectionMethod($object, 'method');
        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invoke($object);

        $anotherReflection = new \ReflectionMethod('SomeClass', 'someMethod');
        $anotherReflection->setAccessible(false);
        $anotherReflection->invokeArgs($object, []);
    }
}

-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector\Fixture;

class ReflectionMethodFixture
{
    public function testReflectionMethod()
    {
        $object = new \stdClass();
        $reflectionMethod = new \ReflectionMethod($object, 'method');
        $result = $reflectionMethod->invoke($object);

        $anotherReflection = new \ReflectionMethod('SomeClass', 'someMethod');
        $anotherReflection->invokeArgs($object, []);
    }
}
