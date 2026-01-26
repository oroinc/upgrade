<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function multipleOptionsAction()
    {
        return new Template(['template' => '@TestBundle/Default/test.html.twig', 'isStreamable' => true]);
    }
}

-----
<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function multipleOptionsAction()
    {
        return new Template('@TestBundle/Default/test.html.twig', stream: true);
    }
}
