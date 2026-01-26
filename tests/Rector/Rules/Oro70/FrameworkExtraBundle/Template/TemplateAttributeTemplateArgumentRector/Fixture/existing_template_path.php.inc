<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CustomerController extends AbstractController
{
    #[Template('@OroCustomer/Customer/custom.html.twig')]
    public function customAction(): array
    {
        return [];
    }

    #[Template('@AnotherBundle/Another/path.html.twig')]
    public function anotherAction(): array
    {
        return [];
    }
}

-----
<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CustomerController extends AbstractController
{
    #[Template('@OroCustomer/Customer/custom.html.twig')]
    public function customAction(): array
    {
        return [];
    }

    #[Template('@AnotherBundle/Another/path.html.twig')]
    public function anotherAction(): array
    {
        return [];
    }
}
