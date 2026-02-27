<?php

namespace Oro\UpgradeToolkit\Rector\Console\CommandRunner;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Base class for running PHP console commands in separate processes
 * with proper error handling and output streaming.
 *
 * Handles locating the PHP executable and resolving the correct binary directory (bin or vendor/bin).
 */
class AbstractCommandRunner
{
    private string $binDir;

    public function __construct(
        private string $projectRoot
    ) {
        $this->binDir = $this->projectRoot . (is_dir($this->projectRoot . '/bin') ? '/bin' : '/vendor/bin');
    }

    public function getPhpExecutable(): string|false
    {
        return (new PhpExecutableFinder())->find();
    }

    public function getBinDirectory(): string
    {
        return $this->binDir;
    }

    public function run(string $binFile, array $parameters, SymfonyStyle $io): int
    {
        $executablePath = $this->getBinDirectory() . "/" . $binFile;
        if (!is_executable($executablePath)) {
            $io->error(sprintf('%s was not found', $binFile));

            return Command::FAILURE;
        }

        $command = array_merge(
            [
                $this->getPhpExecutable(),
                $executablePath,
            ],
            $parameters,
        );

        $process = new Process(command: $command, timeout: null);

        $io->info(sprintf('Running %s …', $process->getCommandLine()));

        $process->run(
            function ($type, $buffer) use ($io) {
                $io->write($buffer);
            }
        );

        return $process->getExitCode() ?? Command::FAILURE;
    }
}
