<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Template]
    public function listAction(): array
    {
        return [];
    }

    #[Template('@OroProduct/Product/existing.html.twig')]
    public function showAction(): array
    {
        return [];
    }
}

-----
<?php

namespace Oro\Bundle\ProductBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Template('@OroProduct/Product/list.html.twig')]
    public function listAction(): array
    {
        return [];
    }

    #[Template('@OroProduct/Product/existing.html.twig')]
    public function showAction(): array
    {
        return [];
    }
}
