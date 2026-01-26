<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function alreadyNamedArgsAction()
    {
        return new Template('@TestBundle/Default/test.html.twig');
    }

    public function mixedArgsAction()
    {
        return new Template('@TestBundle/Default/test.html.twig', stream: true);
    }
}

-----
<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function alreadyNamedArgsAction()
    {
        return new Template('@TestBundle/Default/test.html.twig');
    }

    public function mixedArgsAction()
    {
        return new Template('@TestBundle/Default/test.html.twig', stream: true);
    }
}
