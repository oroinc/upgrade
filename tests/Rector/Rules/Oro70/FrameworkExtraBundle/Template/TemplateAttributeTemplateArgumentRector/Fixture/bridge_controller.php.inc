<?php

namespace Oro\Bridge\CustomerAccount\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AccountController extends AbstractController
{
    #[Template]
    public function dashboardAction(): array
    {
        return [];
    }

    #[Template]
    public function profileAction(): array
    {
        return [];
    }
}

-----
<?php

namespace Oro\Bridge\CustomerAccount\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AccountController extends AbstractController
{
    #[Template('@OroCustomerAccountBridge/Account/dashboard.html.twig')]
    public function dashboardAction(): array
    {
        return [];
    }

    #[Template('@OroCustomerAccountBridge/Account/profile.html.twig')]
    public function profileAction(): array
    {
        return [];
    }
}
