<?php

namespace Oro\UpgradeToolkit\Rector\Console\Command;

use Oro\UpgradeToolkit\Configuration\CommandOption;
use Oro\UpgradeToolkit\Rector\Application\AddedFilesProcessor;
use Oro\UpgradeToolkit\Rector\Application\DeletedFilesProcessor;
use Rector\ValueObject\Configuration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Applies postponed file operations such as adding new files and deleting obsolete files.
 *
 * This command processes files that were queued for addition or deletion during the upgrade process.
 * It can be run in dry-run mode to preview changes before applying them.
 */
#[AsCommand(
    name: 'apply:file:ops',
    description: 'Applies postponed file operations (add/delete files)',
    hidden: false
)]
class FileOperationsCommand extends Command
{
    private InputInterface $input;
    private SymfonyStyle $io;
    private DeletedFilesProcessor $deletedFilesProcessor;
    private AddedFilesProcessor $addedFilesProcessor;

    private bool $runDeletedFilesProcessor = false;
    private bool $runAddedFilesProcessor = false;

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command applies postponed file operations that were queued during the upgrade process.

This includes:
  • <comment>Adding new files</comment> that should be created in the project
  • <comment>Deleting obsolete files</comment> that are no longer needed after the upgrade

<fg=yellow>Usage Examples:</>

  Apply all postponed file operations:
    <info>php %command.full_name%</info>

  Preview changes without applying them (dry-run mode):
    <info>php %command.full_name% --dry-run</info>

  Clear temporary files without applying operations:
    <info>php %command.full_name% --clear-tmp</info>

<fg=yellow>Options:</>
  <info>--dry-run</info>      Preview the operations without actually modifying files
  <info>--clear-tmp</info>     Remove temporary processor files and exit
HELP
            );

        $this->addOption(
            CommandOption::DRY_RUN,
            null,
            InputOption::VALUE_NONE,
            'Only see the diff of changes, do not save them to files'
        );

        $this->addOption(
            CommandOption::CLEAR_TMP,
            null,
            InputOption::VALUE_NONE,
            'Removes Processor`s TMP files'
        );

        parent::configure();
    }

    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);

        $this->deletedFilesProcessor = new DeletedFilesProcessor($this->io);
        $this->addedFilesProcessor = new AddedFilesProcessor($this->io);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->info(sprintf('Perform all needed checkups … '));
        if ($this->shouldRun()) {
            $configuration = new Configuration(isDryRun: $this->input->getOption(CommandOption::DRY_RUN));

            // Process files that should be deleted
            if ($this->runDeletedFilesProcessor) {
                $this->io->info(sprintf('DeletedFilesProcessor is running … '));
                $this->deletedFilesProcessor->process($configuration);
            }

            // Process files that should be added
            if ($this->runAddedFilesProcessor) {
                $this->io->info(sprintf('AddedFilesProcessor is running … '));
                $this->addedFilesProcessor->process($configuration);
            }
        }

        $this->io->info(sprintf('Nothing to process. Cleanup … '));
        $this->deletedFilesProcessor->deleteTmpFile();
        $this->addedFilesProcessor->deleteTmpFile();

        $this->io->success(sprintf('All File Operations are Done'));
        return Command::SUCCESS;
    }

    private function shouldRun(): bool
    {
        if ($this->input->getOption(CommandOption::CLEAR_TMP)) {
            return false;
        }

        if (!empty($this->deletedFilesProcessor->getFilesToDelete())) {
            $this->runDeletedFilesProcessor = true;
        }

        if (!empty($this->addedFilesProcessor->getFilesToAdd())) {
            $this->runAddedFilesProcessor = true;
        }

        return $this->runDeletedFilesProcessor || $this->runAddedFilesProcessor;
    }
}
