<?php

namespace Oro\UpgradeToolkit\Rector\Console\CommandRunner;

use Oro\UpgradeToolkit\Configuration\Commands;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Executes file operations commands (apply:file:ops) in a separate PHP process.
 */
class FileOperationsCommandRunner extends AbstractCommandRunner
{
    public function runFileOps(array $parameters, SymfonyStyle $io): int
    {
        $parameters = array_merge(
            [Commands::APPLY_FILE_OPERATIONS],
            $parameters,
        );

        return $this->run(Commands::UPGRADE_TOOLKIT, $parameters, $io);
    }
}
