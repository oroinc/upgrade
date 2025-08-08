<?php

namespace Test;

use OldBundle\OldClass;

class TestClass
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

class TestClass
{
    public function someMethod()
    {
        $instance = new \NewBundle\NewClass();
        return $instance;
    }
}
