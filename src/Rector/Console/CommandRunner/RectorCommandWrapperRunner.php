<?php

namespace Oro\UpgradeToolkit\Rector\Console\CommandRunner;

use Oro\UpgradeToolkit\Configuration\Commands;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Executes Rector commands through the upgrade-toolkit wrapper in a separate PHP process.
 */
class RectorCommandWrapperRunner extends AbstractCommandRunner
{
    public function runRector(array $parameters, SymfonyStyle $io): int
    {
        $parameters = array_merge(
            [Commands::RECTOR],
            $parameters,
        );

        return $this->run(Commands::UPGRADE_TOOLKIT, $parameters, $io);
    }
}
