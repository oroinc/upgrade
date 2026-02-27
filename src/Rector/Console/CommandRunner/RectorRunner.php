<?php

namespace Oro\UpgradeToolkit\Rector\Console\CommandRunner;

use Oro\UpgradeToolkit\Configuration\Commands;
use Oro\UpgradeToolkit\Configuration\RectorCommands;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Provides methods to run Rector commands
 */
class RectorRunner extends AbstractCommandRunner
{
    public function runProcess(array $parameters, SymfonyStyle $io): int
    {
        $parameters = array_merge(
            [RectorCommands::PROCESS->value],
            $parameters,
        );

        return $this->run(Commands::RECTOR, $parameters, $io);
    }
}
