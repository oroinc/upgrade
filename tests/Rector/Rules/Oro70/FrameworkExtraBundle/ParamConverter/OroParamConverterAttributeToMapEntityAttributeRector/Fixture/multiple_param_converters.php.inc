<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MultiParamController extends AbstractController
{
    #[ParamConverter('user', options: ['mapping' => ['userId' => 'id']])]
    #[ParamConverter('product', options: ['repository_method' => 'findBySlug', 'mapping' => ['slug']])]
    public function showAction($user, $product): array
    {
        return [];
    }
}

-----
<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MultiParamController extends AbstractController
{
    public function showAction(
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ['userId' => 'id'])]
        $user,
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ['slug'], expr: 'repository.findBySlug(slug)')]
        $product
    ): array
    {
        return [];
    }
}
