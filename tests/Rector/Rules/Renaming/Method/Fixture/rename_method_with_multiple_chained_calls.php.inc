<?php

namespace Test;

class MultiChainService
{
    public function getData()
    {
        return [];
    }

    public function data()
    {
        return $this;
    }

    public function get()
    {
        return $this;
    }

    public function toArray()
    {
        return [];
    }
}

class TestClass
{
    public function testMethod()
    {
        $service = new MultiChainService();
        $result = $service->getData();
        return $result;
    }

    public function anotherTest()
    {
        $service = new MultiChainService();
        return $service->getData();
    }
}

-----
<?php

namespace Test;

class MultiChainService
{
    public function getData()
    {
        return [];
    }

    public function data()
    {
        return $this;
    }

    public function get()
    {
        return $this;
    }

    public function toArray()
    {
        return [];
    }
}

class TestClass
{
    public function testMethod()
    {
        $service = new MultiChainService();
        $result = $service->data()->get()->toArray();
        return $result;
    }

    public function anotherTest()
    {
        $service = new MultiChainService();
        return $service->data()->get()->toArray();
    }
}
