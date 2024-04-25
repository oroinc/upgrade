<?php

namespace Acme\Bundle\DemoBundle\Command;

class SomeClass extends \Symfony\Component\Console\Command\Command
{
    public function getDefaultDefinition()
    {
        return '* * * * * ? *';
    }
}

?>
-----
<?php

namespace Acme\Bundle\DemoBundle\Command;

class SomeClass extends \Symfony\Component\Console\Command\Command implements \Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface
{
    public function getDefaultDefinition(): string
    {
        return '* * * * * ? *';
    }
}

?>
