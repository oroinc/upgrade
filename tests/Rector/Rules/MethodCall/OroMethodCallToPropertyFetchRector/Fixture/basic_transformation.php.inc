<?php

use Oro\UpgradeToolkit\Tests\Template;

class TestsClass
{
    public function testMethod()
    {
        $template = new Template();

        $templateValue = $template->getTemplate();
        $template->setTemplate('some-template');
        $template->setTemplate($form->getName());

        $template->setTemplate('value', 'extra_param');

        $template->someOtherMethod();
        $form->anotherMethod('value');

        $otherObject = new \stdClass();
        $otherObject->setTemplate('value');
        $otherObject->getTemplate();
    }
}

-----
<?php

use Oro\UpgradeToolkit\Tests\Template;

class TestsClass
{
    public function testMethod()
    {
        $template = new Template();

        $templateValue = $template->template;
        $template->template = 'some-template';
        $template->template = $form->getName();

        $template->template = 'value';

        $template->someOtherMethod();
        $form->anotherMethod('value');

        $otherObject = new \stdClass();
        $otherObject->setTemplate('value');
        $otherObject->getTemplate();
    }
}
