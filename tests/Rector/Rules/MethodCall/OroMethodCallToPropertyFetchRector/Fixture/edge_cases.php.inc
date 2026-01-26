<?php

class TestClass
{
    public function testEdgeCases(): string
    {
        $template = new Oro\UpgradeToolkit\Tests\Template();
        $config = new Oro\UpgradeToolkit\Tests\Config();

        if ($template->getTemplate()) {
            echo 'has template';
        }

        $array = [
            'template' => $template->getTemplate(),
            'config' => $config->getValue()
        ];

        strlen($template->getTemplate());

        $result = $template->getTemplate() ? 'yes' : 'no';

        $template->setTemplate($config->getValue());

        $obj = new \stdClass();
        $obj->template = $template->getTemplate();

        echo 'Template: ' . $template->getTemplate();

        return $template->getTemplate();
    }
}

-----
<?php

class TestClass
{
    public function testEdgeCases(): string
    {
        $template = new Oro\UpgradeToolkit\Tests\Template();
        $config = new Oro\UpgradeToolkit\Tests\Config();

        if ($template->template) {
            echo 'has template';
        }

        $array = [
            'template' => $template->template,
            'config' => $config->value
        ];

        strlen($template->template);

        $result = $template->template ? 'yes' : 'no';

        $template->template = $config->value;

        $obj = new \stdClass();
        $obj->template = $template->template;

        echo 'Template: ' . $template->template;

        return $template->template;
    }
}
