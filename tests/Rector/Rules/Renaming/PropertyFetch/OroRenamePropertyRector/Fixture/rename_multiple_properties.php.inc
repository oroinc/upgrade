<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
}

class AnotherClass
{
    public $anotherOldProperty;
}

class TargetClass
{
    public function someMethod()
    {
        $obj1 = new SomeClass();
        $obj2 = new AnotherClass();

        $value1 = $obj1->oldProperty;
        $value2 = $obj2->anotherOldProperty;

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

class AnotherClass
{
    public $anotherOldProperty;
}

class TargetClass
{
    public function someMethod()
    {
        $obj1 = new SomeClass();
        $obj2 = new AnotherClass();

        $value1 = $obj1->newProperty;
        $value2 = $obj2->anotherNewProperty;

        return [$value1, $value2];
    }
}
