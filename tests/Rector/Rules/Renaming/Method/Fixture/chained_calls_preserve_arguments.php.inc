<?php

namespace Test;

class ChainedService
{
    public function getConfiguration($param1, $param2 = 'default')
    {
        return [];
    }

    public function config($param1, $param2 = 'default')
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

        $config1 = $service->getConfiguration('value1');
        $config2 = $service->getConfiguration('value1', 'value2');

        $config3 = $service->getConfiguration(
            $this->getParam(),
            'constant_value'
        );

        return [$config1, $config2, $config3];
    }

    private function getParam()
    {
        return 'param';
    }
}

-----
<?php

namespace Test;

class ChainedService
{
    public function getConfiguration($param1, $param2 = 'default')
    {
        return [];
    }

    public function config($param1, $param2 = 'default')
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

        $config1 = $service->config('value1')->all();
        $config2 = $service->config('value1', 'value2')->all();

        $config3 = $service->config(
            $this->getParam(),
            'constant_value'
        )->all();

        return [$config1, $config2, $config3];
    }

    private function getParam()
    {
        return 'param';
    }
}
