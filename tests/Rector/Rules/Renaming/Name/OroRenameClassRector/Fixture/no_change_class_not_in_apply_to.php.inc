<?php

namespace Test;

use OldBundle\OldClass;

class NotInApplyToList
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

class NotInApplyToList
{
    public function someMethod()
    {
        $instance = new OldClass();
        return $instance;
    }
}
