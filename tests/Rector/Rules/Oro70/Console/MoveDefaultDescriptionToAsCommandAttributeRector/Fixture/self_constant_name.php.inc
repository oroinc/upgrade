<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: self::COMMAND_NAME)]
class AnotherCommand extends Command
{
    private const COMMAND_NAME = 'app:another-command';

    protected static $defaultDescription = 'Another command description';
}
-----
<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: self::COMMAND_NAME, description: 'Another command description')]
class AnotherCommand extends Command
{
    private const COMMAND_NAME = 'app:another-command';
}
