<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector\Fixture;

class ReflectionPropertyFixture
{
    public function testReflectionProperty()
    {
        $object = new \stdClass();
        $reflectionProperty = new \ReflectionProperty($object, 'property');
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($object);

        $anotherReflection = new \ReflectionProperty('SomeClass', 'someProperty');
        $anotherReflection->setAccessible(false);
        $anotherReflection->setValue($object, 'value');
    }
}

-----
<?php

namespace Oro\UpgradeToolkit\Tests\Rector\Rules\MethodCall\RemoveReflectionSetAccessibleCallsRector\Fixture;

class ReflectionPropertyFixture
{
    public function testReflectionProperty()
    {
        $object = new \stdClass();
        $reflectionProperty = new \ReflectionProperty($object, 'property');
        $value = $reflectionProperty->getValue($object);

        $anotherReflection = new \ReflectionProperty('SomeClass', 'someProperty');
        $anotherReflection->setValue($object, 'value');
    }
}
