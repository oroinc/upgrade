<?php

use Oro\UpgradeToolkit\Rector\Console\Command\UpgradeCommand;

class SomeService
{
    public function getCommandName(UpgradeCommand $command): ?string
    {
        if (UpgradeCommand::getDefaultName() === $command->getName()) {
            return UpgradeCommand::getDefaultName();
        }

        return null;
    }
}
-----
<?php

use Oro\UpgradeToolkit\Rector\Console\Command\UpgradeCommand;

class SomeService
{
    public function getCommandName(UpgradeCommand $command): ?string
    {
        if ('upgrade' === $command->getName()) {
            return 'upgrade';
        }

        return null;
    }
}
