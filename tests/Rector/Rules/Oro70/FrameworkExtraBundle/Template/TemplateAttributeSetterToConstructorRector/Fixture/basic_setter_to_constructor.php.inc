<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function basicSetterAction()
    {
        $template = new Template();
        $template->setTemplate('@TestBundle/Default/test.html.twig');
        return $template;
    }
}

-----
<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function basicSetterAction()
    {
        $template = new Template('@TestBundle/Default/test.html.twig');
        return $template;
    }
}
