<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function noChangeAlreadyWithArgumentAction()
    {
        $template = new Template('@TestBundle/Default/already.html.twig');
        return $template;
    }

    public function noChangeNoSetterCallAction()
    {
        $template = new Template();
        return $template;
    }
}

-----
<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function noChangeAlreadyWithArgumentAction()
    {
        $template = new Template('@TestBundle/Default/already.html.twig');
        return $template;
    }

    public function noChangeNoSetterCallAction()
    {
        $template = new Template();
        return $template;
    }
}
