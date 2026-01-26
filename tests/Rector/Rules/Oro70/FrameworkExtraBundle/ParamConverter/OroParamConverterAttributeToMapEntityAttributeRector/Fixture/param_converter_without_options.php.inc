<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SimpleController extends AbstractController
{
    #[ParamConverter('item')]
    public function showAction($item): array
    {
        return [];
    }
}

-----
<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SimpleController extends AbstractController
{
    public function showAction(
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity]
        $item
    ): array
    {
        return [];
    }
}
