<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function complexOptionsAction()
    {
        return new Template([
            'template' => '@TestBundle/Default/complex.html.twig',
            'isStreamable' => false,
            'vars' => ['key' => 'value']
        ]);
    }
}

-----
<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function complexOptionsAction()
    {
        return new Template('@TestBundle/Default/complex.html.twig', stream: false, vars: ['key' => 'value']);
    }
}
