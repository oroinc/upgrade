<?php

namespace Test;

class ServiceInterface
{
    public $serviceProperty;
}

interface InterfaceClass
{
    public function someMethod();
}

class ServiceImplementer implements InterfaceClass
{
    public function someMethod()
    {
        $service = new ServiceInterface();
        return $service->serviceProperty;
    }

    public function anotherMethod()
    {
        $service = new ServiceInterface();
        $service->serviceProperty = 'value';
    }
}
-----
<?php

namespace Test;

class ServiceInterface
{
    public $serviceProperty;
}

interface InterfaceClass
{
    public function someMethod();
}

class ServiceImplementer implements InterfaceClass
{
    public function someMethod()
    {
        $service = new ServiceInterface();
        return $service->newServiceProperty;
    }

    public function anotherMethod()
    {
        $service = new ServiceInterface();
        $service->newServiceProperty = 'value';
    }
}
