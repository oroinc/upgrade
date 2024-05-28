<?php

$this->send(Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector\Mocks\Topics\Topics::SEND_AUTO_RESPONSE, []);
$this->receive(Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector\Mocks\Topics\Topics::SEND_AUTO_RESPONSES, []);
$this->send(Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector\Mocks\Topics\Topics::SEND_EMAIL_TEMPLATE, []);
$this->send(Oro\UpgradeToolkit\Tests\Rector\Rules\Oro51\ClassConstantToStaticMethodCallRector\Mocks\Topics\Topics::ADD_ASSOCIATION_TO_EMAIL, []);

?>
-----
<?php

$this->send(\Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponsesTopic::getName(), []);
$this->receive(\Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponsesTopic::getName(), []);
$this->send(\Oro\Bundle\EmailBundle\Async\Topic\SendEmailTemplateTopic::getName(), []);
$this->send(\Oro\Bundle\EmailBundle\Async\Topic\AddEmailAssociationTopic::getName(), []);

?>
