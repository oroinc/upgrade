<?php

namespace Test;

class SimpleService
{
    public function oldMethod()
    {
        return 'old';
    }

    public function newMethod()
    {
        return 'new';
    }
}

class TestClass
{
    public function testMethod()
    {
        $service = new SimpleService();
        return $service->oldMethod();
    }

    public function anotherMethod()
    {
        $service = new SimpleService();
        $result = $service->oldMethod();
        return $result;
    }
}

-----
<?php

namespace Test;

class SimpleService
{
    public function oldMethod()
    {
        return 'old';
    }

    public function newMethod()
    {
        return 'new';
    }
}

class TestClass
{
    public function testMethod()
    {
        $service = new SimpleService();
        return $service->newMethod();
    }

    public function anotherMethod()
    {
        $service = new SimpleService();
        $result = $service->newMethod();
        return $result;
    }
}
