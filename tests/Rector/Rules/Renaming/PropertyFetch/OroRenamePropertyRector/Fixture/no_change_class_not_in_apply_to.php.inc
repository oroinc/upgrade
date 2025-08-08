<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
}

class NotInApplyToList
{
    public function someMethod()
    {
        $obj = new SomeClass();
        return $obj->oldProperty;
    }

    public function setProperty()
    {
        $obj = new SomeClass();
        $obj->oldProperty = 'test';
    }
}
-----
<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
}

class NotInApplyToList
{
    public function someMethod()
    {
        $obj = new SomeClass();
        return $obj->oldProperty;
    }

    public function setProperty()
    {
        $obj = new SomeClass();
        $obj->oldProperty = 'test';
    }
}
