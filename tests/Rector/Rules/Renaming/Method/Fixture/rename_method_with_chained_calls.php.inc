<?php

namespace Test;

class ChainedService
{
    public function getConfiguration()
    {
        return [];
    }

    public function config()
    {
        return $this;
    }

    public function all()
    {
        return [];
    }
}

class TestClass
{
    public function testMethod()
    {
        $service = new ChainedService();
        $config = $service->getConfiguration();
        return $config;
    }
}

-----
<?php

namespace Test;

class ChainedService
{
    public function getConfiguration()
    {
        return [];
    }

    public function config()
    {
        return $this;
    }

    public function all()
    {
        return [];
    }
}

class TestClass
{
    public function testMethod()
    {
        $service = new ChainedService();
        $config = $service->config()->all();
        return $config;
    }
}
