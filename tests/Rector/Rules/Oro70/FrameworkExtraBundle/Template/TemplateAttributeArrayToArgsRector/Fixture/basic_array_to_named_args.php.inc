<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function basicArrayAction()
    {
        return new Template(['template' => '@TestBundle/Default/test.html.twig']);
    }
}

-----
<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function basicArrayAction()
    {
        return new Template('@TestBundle/Default/test.html.twig');
    }
}
