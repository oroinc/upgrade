<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
}

class TargetClass
{
    private SomeClass $someClassInstance;

    public function __construct()
    {
        $this->someClassInstance = new SomeClass();
    }

    public function getValue()
    {
        return $this->someClassInstance->oldProperty;
    }

    public function setValue($value)
    {
        $this->someClassInstance->oldProperty = $value;
    }
}
-----
<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
}

class TargetClass
{
    private SomeClass $someClassInstance;

    public function __construct()
    {
        $this->someClassInstance = new SomeClass();
    }

    public function getValue()
    {
        return $this->someClassInstance->newProperty;
    }

    public function setValue($value)
    {
        $this->someClassInstance->newProperty = $value;
    }
}
