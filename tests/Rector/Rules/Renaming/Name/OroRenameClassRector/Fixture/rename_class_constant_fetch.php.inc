<?php

namespace Test;

use OldBundle\OldClass;

class TestClass
{
    public function someMethod()
    {
        $constant = OldClass::SOME_CONSTANT;
        return $constant;
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
        $constant = \NewBundle\NewClass::SOME_CONSTANT;
        return $constant;
    }
}
