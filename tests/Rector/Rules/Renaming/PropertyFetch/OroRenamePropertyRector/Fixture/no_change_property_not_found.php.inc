<?php

namespace Test;

class SomeClass
{
    public $nonExistentProperty;
}

class TargetClass
{
    public function someMethod()
    {
        $obj = new SomeClass();
        return $obj->nonExistentProperty;
    }
}
-----
<?php

namespace Test;

class SomeClass
{
    public $nonExistentProperty;
}

class TargetClass
{
    public function someMethod()
    {
        $obj = new SomeClass();
        return $obj->nonExistentProperty;
    }
}
