<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    #[ParamConverter('user', options: ['repository_method' => 'findByEmail', 'mapping' => ['email' => 'email']])]
    public function findByEmailAction($user): array
    {
        return [];
    }
}

-----
<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    public function findByEmailAction(
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ['email' => 'email'], expr: 'repository.findByEmail(email)')]
        $user
    ): array
    {
        return [];
    }
}
