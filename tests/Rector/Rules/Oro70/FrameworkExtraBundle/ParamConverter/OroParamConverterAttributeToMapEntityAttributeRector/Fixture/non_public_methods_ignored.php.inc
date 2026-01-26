<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IgnoredController extends AbstractController
{
    // Private method should be ignored
    #[ParamConverter('item', options: ['mapping' => ['id' => 'id']])]
    private function privateAction($item): array
    {
        return [];
    }

    // Protected method should be ignored
    #[ParamConverter('item', options: ['mapping' => ['id' => 'id']])]
    protected function protectedAction($item): array
    {
        return [];
    }
}

-----
<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IgnoredController extends AbstractController
{
    // Private method should be ignored
    #[ParamConverter('item', options: ['mapping' => ['id' => 'id']])]
    private function privateAction($item): array
    {
        return [];
    }

    // Protected method should be ignored
    #[ParamConverter('item', options: ['mapping' => ['id' => 'id']])]
    protected function protectedAction($item): array
    {
        return [];
    }
}
