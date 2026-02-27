<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'app:skip-command', description: 'Already has description')]
class SkipCommand extends Command
{
    protected static $defaultDescription = 'Some description';
}
