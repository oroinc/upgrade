<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function emptyArrayAction()
    {
        return new Template([]);
    }
}

-----
<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function emptyArrayAction()
    {
        return new Template('');
    }
}
