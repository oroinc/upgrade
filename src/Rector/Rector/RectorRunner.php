<?php

namespace Oro\UpgradeToolkit\Rector\Rector;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Provides methods to run Rector commands
 */
class RectorRunner
{
    private PhpExecutableFinder $phpExecutableFinder;
    private string $binDir;

    public function __construct(
        private string $projectRoot
    ) {
        $this->phpExecutableFinder = new PhpExecutableFinder();
        $this->binDir = $this->projectRoot . (is_dir($this->projectRoot . '/bin') ? '/bin' : '/vendor/bin');
    }

    public function process(array $parameters, SymfonyStyle $io): ?int
    {
        $rectorPath = $this->binDir . '/rector';
        if (!is_executable($rectorPath)) {
            $io->warning('Rector was not found');

            return Command::FAILURE;
        }

        $command = array_merge(
            [
                $this->phpExecutableFinder->find(),
                $rectorPath,
                'process',
            ],
            $parameters
        );

        $rectorProcess = new Process(command: $command, timeout: null);
        $io->writeln(sprintf('<info>Running %s … </info>', $rectorProcess->getCommandLine()));
        $rectorProcess->run(
            function ($type, $buffer) use ($io) {
                $io->write($buffer);
            }
        );

        return $rectorProcess->getExitCode();
    }
}
