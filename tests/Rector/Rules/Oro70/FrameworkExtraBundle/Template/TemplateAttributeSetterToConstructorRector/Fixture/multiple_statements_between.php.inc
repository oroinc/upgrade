<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function multipleStatementsAction()
    {
        $template = new Template();
        $otherVar = 'some value';
        $template->setTemplate('@TestBundle/Default/multiple.html.twig');
        $anotherVar = 'another value';
        return $template;
    }
}

-----
<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function multipleStatementsAction()
    {
        $template = new Template('@TestBundle/Default/multiple.html.twig');
        $otherVar = 'some value';
        $anotherVar = 'another value';
        return $template;
    }
}
