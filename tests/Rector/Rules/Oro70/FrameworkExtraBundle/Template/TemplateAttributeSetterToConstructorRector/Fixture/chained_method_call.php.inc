<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function chainedCallsAction()
    {
        $template = new Template();
        return $template->setTemplate('@TestBundle/Default/chained.html.twig');
    }
}

-----
<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function chainedCallsAction()
    {
        return new Template('@TestBundle/Default/chained.html.twig');
    }
}
