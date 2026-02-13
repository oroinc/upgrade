<?php

namespace Test;

interface InterfaceWithMethod
{
    public function oldInterfaceMethod();
}

-----
<?php

namespace Test;

interface InterfaceWithMethod
{
    public function newInterfaceMethod();
}
