<?php

namespace Oro\Bundle\UserBundle\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    #[Template]
    public function dashboard(): array
    {
        return [];
    }

    #[Template]
    public function profile(): array
    {
        return [];
    }

    #[Template]
    public function settingsAction(): array
    {
        return [];
    }
}

-----
<?php

namespace Oro\Bundle\UserBundle\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    #[Template('@OroUser/User/dashboard.html.twig')]
    public function dashboard(): array
    {
        return [];
    }

    #[Template('@OroUser/User/profile.html.twig')]
    public function profile(): array
    {
        return [];
    }

    #[Template('@OroUser/User/settings.html.twig')]
    public function settingsAction(): array
    {
        return [];
    }
}
