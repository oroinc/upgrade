<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
    public $someOtherProperty;
}

class TargetClass
{
    public function someMethod()
    {
        $obj = new SomeClass();
        $value = $obj->oldProperty;
        return $value;
    }
}
-----
<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
    public $someOtherProperty;
}

class TargetClass
{
    public function someMethod()
    {
        $obj = new SomeClass();
        $value = $obj->newProperty;
        return $value;
    }
}
