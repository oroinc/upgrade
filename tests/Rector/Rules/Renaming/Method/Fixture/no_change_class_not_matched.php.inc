<?php

namespace Test;

class DifferentClass
{
    public function oldMethod()
    {
        return 'old';
    }
}

class TestClass
{
    public function testMethod()
    {
        $service = new DifferentClass();

        return $service->oldMethod();
    }
}

-----
<?php

namespace Test;

class DifferentClass
{
    public function oldMethod()
    {
        return 'old';
    }
}

class TestClass
{
    public function testMethod()
    {
        $service = new DifferentClass();

        return $service->oldMethod();
    }
}
