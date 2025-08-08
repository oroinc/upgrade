<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
}

class ParentClass
{
    public function useProperty()
    {
        $obj = new SomeClass();
        return $obj->oldProperty;
    }
}

class ChildClass extends ParentClass
{
    public function childMethod()
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

class ParentClass
{
    public function useProperty()
    {
        $obj = new SomeClass();
        return $obj->newProperty;
    }
}

class ChildClass extends ParentClass
{
    public function childMethod()
    {
        $obj = new SomeClass();
        $obj->newProperty = 'test';
    }
}
