<?php

class TestClass
{
    public function testSkipTransformation()
    {
        $template = new Oro\UpgradeToolkit\Tests\Template();
        $form = new Oro\UpgradeToolkit\Tests\Form();

        $template->setTitle('title');
        $template->getTitle();
        $form->setType('text');
        $form->getType();

        Template::setTemplate('static_value');
        Form::getName();

        $stdClass = new \stdClass();
        $stdClass->setTemplate = 'value';

        $nullObj = null;
        $nullObj?->setTemplate('value');
    }
}

-----
<?php

class TestClass
{
    public function testSkipTransformation()
    {
        $template = new Oro\UpgradeToolkit\Tests\Template();
        $form = new Oro\UpgradeToolkit\Tests\Form();

        $template->setTitle('title');
        $template->getTitle();
        $form->setType('text');
        $form->getType();

        Template::setTemplate('static_value');
        Form::getName();

        $stdClass = new \stdClass();
        $stdClass->setTemplate = 'value';

        $nullObj = null;
        $nullObj?->setTemplate('value');
    }
}
