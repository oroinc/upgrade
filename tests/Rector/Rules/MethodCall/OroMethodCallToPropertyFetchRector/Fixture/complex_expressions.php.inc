<?php

class TestClass
{
    public function testComplexExpressions(): void
    {
        $template = new \Oro\UpgradeToolkit\Tests\Template();
        $otherTemplate = new \Oro\UpgradeToolkit\Tests\Template();

        $template->setTemplate($otherTemplate->getTemplate() . '_suffix');

        $values = ['key' => 'value'];
        $template->setTemplate($values['key']);

        $template->setTemplate(strtoupper('template_name'));

        $templateName = 'my_template';
        $template->setTemplate($templateName);

        $result = $template->getTemplate() . '_' . $otherTemplate->getTemplate();
    }
}

-----
<?php

class TestClass
{
    public function testComplexExpressions(): void
    {
        $template = new \Oro\UpgradeToolkit\Tests\Template();
        $otherTemplate = new \Oro\UpgradeToolkit\Tests\Template();

        $template->template = $otherTemplate->template . '_suffix';

        $values = ['key' => 'value'];
        $template->template = $values['key'];

        $template->template = strtoupper('template_name');

        $templateName = 'my_template';
        $template->template = $templateName;

        $result = $template->template . '_' . $otherTemplate->template;
    }
}
