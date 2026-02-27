<?php

namespace Oro\UpgradeToolkit\Rector\Console\Command;

use Oro\UpgradeToolkit\Configuration\CommandOption;
use Oro\UpgradeToolkit\Configuration\Commands;
use Oro\UpgradeToolkit\Configuration\RectorCommands;
use Oro\UpgradeToolkit\Rector\Console\CommandRunner\FileOperationsCommandRunner;
use Oro\UpgradeToolkit\Rector\Console\CommandRunner\RectorRunner;
use Oro\UpgradeToolkit\Rector\Console\ProcessConfigureDecorator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rector command wrapper that enhances standard Rector functionality.
 *
 * This wrapper allows combining Rector code refactoring with additional
 * pre- and post-processing operations such as file manipulations,
 * temporary file cleanup, and custom transformations.
 *
 * Supported wrapped commands:
 * - process: Runs Rector process command with additional file processors
 */
#[AsCommand(
    name: 'rector',
    description: 'Executes Rector commands with pre- and post-processing capabilities',
    hidden: false
)]
class RectorCommandWrapper extends Command
{
    private const BASE_PATH = '.';
    private InputInterface $input;
    private SymfonyStyle $io;
    private string $projectRoot;

    #[\Override]
    protected function configure(): void
    {
        $this->setHelp(
            <<<'HELP'
The <info>%command.name%</info> command wraps Rector commands to provide additional
functionality like pre-processing, post-processing, and file operations.

<comment>Basic Usage:</comment>
  <info>php %command.full_name% process [source] [options]</info>

<comment>Examples:</comment>

  Run Rector process on src directory:
    <info>php %command.full_name% process src</info>

  Run with dry-run mode (no changes written):
    <info>php %command.full_name% process src --dry-run</info>

  Run with specific configuration file:
    <info>php %command.full_name% process src --config=rector-custom.php</info>

  Run with debugging enabled:
    <info>php %command.full_name% process src --debug</info>

  Run without progress bar (useful for CI):
    <info>php %command.full_name% process src --no-progress-bar --no-diffs</info>

  Filter by specific rule:
    <info>php %command.full_name% process src --only=SomeRectorRule</info>

<comment>Supported Commands:</comment>
  - <info>process</info>   Analyze and refactor PHP code with Rector
    <comment>Features:</comment>
      - Automatic file processors execution after successful Rector run
      - Temporary files cleanup on failure

For more information about Rector options, visit: https://getrector.com
HELP
        );

        $this->addArgument(
            CommandOption::WRAPPED_COMMAND,
            InputArgument::REQUIRED,
            'Rector command to execute (e.g., "process")'
        );

        ProcessConfigureDecorator::decorate($this);
        parent::configure();
    }

    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);

        $this->projectRoot = realpath(self::BASE_PATH);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $wrappedCommand = (string) $this->input->getArgument(CommandOption::WRAPPED_COMMAND);
        $rectorCommand = RectorCommands::tryFrom($wrappedCommand);

        return match ($rectorCommand) {
            RectorCommands::PROCESS => $this->runRectorProcessAndFileProcessors(),

            default => $this->handleUnsupportedCommand($wrappedCommand),
        };
    }

    private function runRectorProcessAndFileProcessors(): int
    {
        $status = $this->runRectorProcess();

        /**  @see \Rector\Console\Command\ProcessCommand::resolveReturnCode */
        if (in_array($status, [Command::SUCCESS, Command::INVALID], true)) {
            return $this->runFileProcessors();
        }

        $this->io->error(
            [
                sprintf('Rector run FAILED'),
                sprintf('Fix the issues below and re-run the command'),
            ]
        );
        $this->deleteTmpFiles();

        return $status;
    }

    private function handleUnsupportedCommand(string $wrappedCommand): int
    {
        $this->io->warning(
            [
                sprintf('%s is not supported yet', $wrappedCommand),
                sprintf('Try to run'),
                sprintf('php bin/%s', implode(' ', $this->input->getRawTokens())),
                sprintf('instead'),
            ]
        );

        return Command::FAILURE;
    }

    private function runRectorProcess(): int
    {
        $parameters = $this->input->getRawTokens();
        foreach ($parameters as $key => $token) {
            if (in_array($token, [Commands::RECTOR, RectorCommands::PROCESS->value], true)) {
                unset($parameters[$key]);
            }
        }

        return (new RectorRunner($this->projectRoot))->runProcess($parameters, $this->io);
    }

    private function runFileProcessors(array $parameters = []): int
    {
        !$this->input->getOption(CommandOption::DRY_RUN) ?: $parameters[] = '--dry-run';

        return (new FileOperationsCommandRunner($this->projectRoot))->runFileOps($parameters, $this->io);
    }

    private function deleteTmpFiles(): void
    {
        $this->runFileProcessors(['--clear-tmp']);
    }
}
