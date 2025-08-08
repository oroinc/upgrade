<?php

namespace Test;

use OldNamespace\Service;

class ServiceUser
{
    private Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function useService(): Service
    {
        return new Service();
    }
}
-----
<?php

namespace Test;

use OldNamespace\Service;

class ServiceUser
{
    private \NewNamespace\Service $service;

    public function __construct(\NewNamespace\Service $service)
    {
        $this->service = $service;
    }

    public function useService(): \NewNamespace\Service
    {
        return new \NewNamespace\Service();
    }
}
