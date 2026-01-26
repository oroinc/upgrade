<?php

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class TestController
{
    public function sensioTemplateAction()
    {
        $template = new Template();
        $template->setTemplate('@TestBundle/Default/sensio.html.twig');
        return $template;
    }
}

-----
<?php

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class TestController
{
    public function sensioTemplateAction()
    {
        $template = new Template('@TestBundle/Default/sensio.html.twig');
        return $template;
    }
}
