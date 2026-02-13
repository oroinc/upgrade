<?php

namespace Test;

class ChainedService
{
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
    public function testMethod(?ChainedService $service)
    {
        $config = $service?->getConfiguration();
        return $config;
    }
}

-----
<?php

namespace Test;

class ChainedService
{
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
    public function testMethod(?ChainedService $service)
    {
        $config = $service?->config()->all();
        return $config;
    }
}
