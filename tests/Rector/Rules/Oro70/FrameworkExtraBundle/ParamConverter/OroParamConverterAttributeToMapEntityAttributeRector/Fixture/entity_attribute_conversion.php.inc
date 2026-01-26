<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CategoryController extends AbstractController
{
    #[Entity('category', options: ['entity_manager' => 'custom', 'mapping' => ['slug' => 'slug']])]
    public function showAction($category): array
    {
        return [];
    }
}

-----
<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CategoryController extends AbstractController
{
    public function showAction(
        #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(mapping: ['slug' => 'slug'])]
        $category
    ): array
    {
        return [];
    }
}
