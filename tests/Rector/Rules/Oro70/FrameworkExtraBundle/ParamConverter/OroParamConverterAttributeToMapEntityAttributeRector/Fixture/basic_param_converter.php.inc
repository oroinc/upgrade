<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[ParamConverter('product', options: ['mapping' => ['id' => 'id']])]
    public function showAction($product): array
    {
        return [];
    }
}

-----
<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    public function showAction(
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ['id' => 'id'])]
        $product
    ): array
    {
        return [];
    }
}
