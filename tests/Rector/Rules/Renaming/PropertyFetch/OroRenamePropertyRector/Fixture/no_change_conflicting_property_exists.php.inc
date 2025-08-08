<?php

namespace Test;

class SomeClass
{
    public $oldProperty;
    public $newProperty; // Conflicting property already exists
}

class TargetClass
{
    public function someMethod()
    {
        $obj = new SomeClass();
        return $obj->newProperty;
    }
}
