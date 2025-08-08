<?php

namespace Test;

use OldInterface\InterfaceA;

interface TestInterface
{
    // This is a test interface
}

class InterfaceImplementer implements TestInterface
{
    public function someMethod()
    {
        $constant = InterfaceA::SOME_CONSTANT;
        return $constant;
    }
}
-----
<?php

namespace Test;

use OldInterface\InterfaceA;

interface TestInterface
{
    // This is a test interface
}

class InterfaceImplementer implements TestInterface
{
    public function someMethod()
    {
        $constant = \NewInterface\InterfaceA::SOME_CONSTANT;
        return $constant;
    }
}
