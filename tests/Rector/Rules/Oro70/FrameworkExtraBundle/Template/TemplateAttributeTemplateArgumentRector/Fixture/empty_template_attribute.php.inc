<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CustomerController extends AbstractController
{
    #[Template]
    public function indexAction(): array
    {
        return [];
    }

    #[Template]
    public function viewAction(): array
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
    #[Template('@OroCustomer/Customer/index.html.twig')]
    public function indexAction(): array
    {
        return [];
    }

    #[Template('@OroCustomer/Customer/view.html.twig')]
    public function viewAction(): array
    {
        return [];
    }
}
