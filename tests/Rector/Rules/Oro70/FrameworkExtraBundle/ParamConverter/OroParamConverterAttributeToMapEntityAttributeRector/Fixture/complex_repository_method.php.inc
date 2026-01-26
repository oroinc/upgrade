<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    #[ParamConverter('order', options: [
        'repository_method' => 'findActiveOrder',
        'mapping' => ['userId', 'status'],
        'map_method_signature' => true
    ])]
    public function getOrderAction($order): array
    {
        return [];
    }
}

-----
<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    public function getOrderAction(
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ['userId', 'status'], expr: 'repository.findActiveOrder(userId, status)')]
        $order
    ): array
    {
        return [];
    }
}
