<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'app:my-command')]
class MyCommand extends Command
{
    protected static $defaultDescription = 'My command description';
}
-----
<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'app:my-command', description: 'My command description')]
class MyCommand extends Command
{
}
