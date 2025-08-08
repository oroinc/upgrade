<?php

namespace Test;

use OldBundle\OldClass;

class TestParentClass
{
    // This is a parent class
}

class TestClass extends TestParentClass
{
    public function someMethod()
    {
        $instance = new OldClass();
        return $instance;
    }
}
-----
<?php

namespace Test;

use OldBundle\OldClass;

class TestParentClass
{
    // This is a parent class
}

class TestClass extends TestParentClass
{
    public function someMethod()
    {
        $instance = new \NewBundle\NewClass();
        return $instance;
    }
}
