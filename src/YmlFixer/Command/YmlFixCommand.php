<?php

namespace Oro\UpgradeToolkit\YmlFixer\Command;

use Oro\UpgradeToolkit\Configuration\CommandOption;
use Oro\UpgradeToolkit\YmlFixer\Finder\YmlFilesFinder;
use Oro\UpgradeToolkit\YmlFixer\Processor\AppYmlFileProcessor;
use Oro\UpgradeToolkit\YmlFixer\ValueObject\Configuration;
use Oro\UpgradeToolkit\YmlFixer\ValueObject\YmlDefinition;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command is a part of the upgrade-toolkit
 * and runs automatically when the php bin/upgrade-toolkit is running
 */
#[AsCommand(
    name: 'yml:fix',
    description: 'Applies needed inspections to upgrade the .yml source code',
    aliases: ['yf', 'y:f'],
    hidden: false
)]
class YmlFixCommand extends Command
{
    private InputInterface $input;
    private SymfonyStyle $io;
    private Configuration $configuration;
    private const BUILDER_HEADER = "    ---------- begin diff ----------\n";

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        // @codingStandardsIgnoreStart
        $this
            ->addOption(CommandOption::SOURCE, null, InputArgument::OPTIONAL, 'Directory to be processed', 'src')
            ->addOption(CommandOption::DRY_RUN, null, InputOption::VALUE_NONE, 'Only see the diff of changes, do not save them to files')
            ->addOption(CommandOption::DEBUG, null, InputOption::VALUE_NONE, 'Display debug output');

        $this->setHelp(
            <<<'HELP'
The <info>%command.name%</info> command is a part of the application upgrade tool. 
It processes most of the .yml files changes in order to upgrade the application.
It doesn’t upgrade Twig, JS, or SCSS.
In most cases, the command can be used without any options.

  <info>php %command.full_name%</info>

The <info>--source</info> option can be used to specify the directory of the application to be processed:
It can be useful when the source root directory name is different from 'src'
or it is needed to process a part of application. (E.g.: Newly added bundle, etc.)

    <info>php %command.full_name% --source=scr//Oro//Bundle//NewBundle</info>
    will apply inspections to the PHP files placed in the src/Oro/Bundle/NewBundle directory recursively

The <info>--dry-run</info> option can be used to list the diff of changes without applying them:

    <info>php %command.full_name% --dry-run</info>

The <info>--debug</info> option is used to display debug output:

    <info>php %command.full_name% --debug</info>

Please see below an example with the most common usage flow:
1. Check the list of changes that will be applied

    <info>php %command.full_name% --dry-run</info>
 
 2. Apply changes

    <info>php %command.full_name%</info>

HELP
        );

        $this
            ->addUsage('--source=<sourceroot>')
            ->addUsage('--dry-run')
            ->addUsage('--debug');
        // @codingStandardsIgnoreEnd
        parent::configure();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);

        $this->configuration = new Configuration(
            source: $this->input->getOption(CommandOption::SOURCE),
            isDryRun: $this->input->getOption(CommandOption::DRY_RUN),
            isDebug: $this->input->getOption(CommandOption::DEBUG),
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!realpath('./' . $this->input->getOption(CommandOption::SOURCE))) {
            $this->io->error('Invalid source directory');
            return Command::FAILURE;
        }
        // 1. Find .yml files
        $fileFinder = new YmlFilesFinder();
        $paths = $fileFinder->findAllYmlFiles($this->input->getOption(CommandOption::SOURCE));
        if (empty($paths)) {
            $this->io->error('The given source path do not match any .yml files');
            return Command::FAILURE;
        }
        $this->configuration->paths = $paths;
        // 2. Run YmlFixer
        $appFileProcessor = new AppYmlFileProcessor();
        $results = $appFileProcessor->run($this->configuration, $this->io);
        // 3. Report
        if (empty($results)) {
            $this->io->warning(sprintf('Any rules were not applied to the .yml files'));
            return Command::SUCCESS;
        }

        return $this->report($results);
    }

    private function report(array $results): int
    {
        [$changesCount, $errorsCount] = $this->countChangesAndErrors($results);

        0 === $changesCount ?: $this->io->title(sprintf('%s .yml files with changes', $changesCount));
        $changesCount < 0 ?: $this->reportChanges($results);

        if (0 === $changesCount && 0 === $errorsCount) {
            $this->io->warning(sprintf('Nothing to change'));
        }

        if ($errorsCount > 0) {
            $this->reportErrors($results);
        }

        return 0 === $errorsCount ? Command::SUCCESS : Command::FAILURE;
    }

    private function reportChanges(array $results): void
    {
        $changesCount = 0;
        /** @var YmlDefinition $ymlDefinition */
        foreach ($results as $ymlDefinition) {
            if ($ymlDefinition->isUpdated() && empty($ymlDefinition->getErrors())) {
                $builder = new UnifiedDiffOutputBuilder(
                    self::BUILDER_HEADER,
                    false
                );
                $differ = new Differ($builder);
                $diff = $differ->diff(
                    $ymlDefinition->getStringDefinition(),
                    $ymlDefinition->getUpdatedStringDefinition()
                );
                if (self::BUILDER_HEADER !== $diff) {
                    $changesCount++;

                    $source = preg_quote($this->configuration->source, '/');
                    $relPath = preg_replace('/.*(' . $source . '.*)$/', '$1', $ymlDefinition->getFilePath());
                    $this->io->writeln(sprintf('%s) %s', $changesCount, $relPath));

                    print $diff;

                    $this->io->writeln('    ----------- end diff -----------');
                    $this->io->writeln(sprintf('Applied Rules:'));
                    array_map(function ($appliedRule) {
                        $this->io->writeln(sprintf(' * %s', $appliedRule));
                    }, $ymlDefinition->getAppliedRules());
                    $this->io->writeln(' ');
                }
            }
        }
    }

    private function reportErrors(array $results): void
    {
        $this->io->title(sprintf('Skipped .yml files:'));

        $errorsCount = 0;
        /** @var YmlDefinition $ymlDefinition */
        foreach ($results as $ymlDefinition) {
            if (!empty($ymlDefinition->getErrors())) {
                $errorsCount++;

                $source = preg_quote($this->configuration->source, '/');
                $relPath = preg_replace('/.*(' . $source . '.*)$/', '$1', $ymlDefinition->getFilePath());
                $this->io->writeln(sprintf('%s) File: %s', $errorsCount, $relPath));

                $this->io->writeln(sprintf('Errors:'));
                /** @var \Throwable $error */
                foreach ($ymlDefinition->getErrors() as $index => $error) {
                    $this->io->writeln(sprintf('- %s. Message: %s', $index + 1, $error->getMessage()));
                    if ($this->configuration->isDebug) {
                        $this->io->writeln(sprintf('Trace: %s', $error->getTraceAsString()));
                    }
                }
            }
        }
    }

    private function countChangesAndErrors(array $results): array
    {
        $changesCount = 0;
        $errorsCount = 0;
        array_map(function ($ymlDefinition) use (&$changesCount, &$errorsCount) {
            if ($ymlDefinition->isUpdated()
                && empty($ymlDefinition->getErrors())
                && $ymlDefinition->getStringDefinition() !== $ymlDefinition->getUpdatedStringDefinition()
            ) {
                $changesCount++;
            } elseif (!empty($ymlDefinition->getErrors())) {
                $errorsCount++;
            }
        }, $results);

        return [$changesCount, $errorsCount];
    }
}
