<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
}

class DifferentClass
{
    public $oldProperty; // Same property name, but different class
}

class TargetClass
{
    public function someMethod()
    {
        $obj1 = new SomeClass();
        $obj2 = new DifferentClass();

        // Only SomeClass should be renamed
        $value1 = $obj1->oldProperty;
        $value2 = $obj2->oldProperty; // Should not change

        return [$value1, $value2];
    }
}
-----
<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
}

class DifferentClass
{
    public $oldProperty; // Same property name, but different class
}

class TargetClass
{
    public function someMethod()
    {
        $obj1 = new SomeClass();
        $obj2 = new DifferentClass();

        // Only SomeClass should be renamed
        $value1 = $obj1->newProperty;
        $value2 = $obj2->oldProperty; // Should not change

        return [$value1, $value2];
    }
}
