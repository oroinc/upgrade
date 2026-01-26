<?php

use Some\Other\Template;
use Symfony\Bridge\Twig\Attribute\Template as TwigTemplate;

class TestController
{
    public function otherTemplateClassAction()
    {
        return new Template(['template' => '@TestBundle/Default/test.html.twig']);
    }

    public function correctTemplateClassAction()
    {
        return new TwigTemplate(['template' => '@TestBundle/Default/test.html.twig']);
    }
}

-----
<?php

use Some\Other\Template;
use Symfony\Bridge\Twig\Attribute\Template as TwigTemplate;

class TestController
{
    public function otherTemplateClassAction()
    {
        return new Template(['template' => '@TestBundle/Default/test.html.twig']);
    }

    public function correctTemplateClassAction()
    {
        return new TwigTemplate('@TestBundle/Default/test.html.twig');
    }
}
