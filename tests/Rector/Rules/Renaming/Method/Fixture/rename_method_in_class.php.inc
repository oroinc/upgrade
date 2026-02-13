<?php

namespace Test;

class ClassWithMethod
{
    public function oldClassMethod()
    {
        return 'old';
    }
}

-----
<?php

namespace Test;

class ClassWithMethod
{
    public function newClassMethod()
    {
        return 'old';
    }
}
