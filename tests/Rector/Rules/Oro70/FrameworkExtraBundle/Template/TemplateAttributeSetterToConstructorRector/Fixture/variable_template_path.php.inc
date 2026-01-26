<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function variableTemplateAction()
    {
        $templatePath = '@TestBundle/Default/variable.html.twig';
        $template = new Template();
        $template->setTemplate($templatePath);
        return $template;
    }
}

-----
<?php

use Symfony\Bridge\Twig\Attribute\Template;

class TestController
{
    public function variableTemplateAction()
    {
        $templatePath = '@TestBundle/Default/variable.html.twig';
        $template = new Template($templatePath);
        return $template;
    }
}
