<?php

namespace Oro\UpgradeToolkit\Rector\Console\Command;

use Nette\Utils\FileSystem;
use Oro\UpgradeToolkit\Configuration\CommandOption;
use Oro\UpgradeToolkit\Configuration\SignatureConfig;
use Oro\UpgradeToolkit\Rector\Rector\RectorRunner;
use Oro\UpgradeToolkit\Rector\Signature\SignatureBuilder;
use Oro\UpgradeToolkit\Rector\Signature\SourceListManipulator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\VarExporter\VarExporter;

#[AsCommand(
    name: 'upgrade',
    description: 'Applies needed inspections to upgrade the source code to the required Oro version',
    hidden: false
)]
class UpgradeCommand extends Command
{
    private const BASE_PATH = '.';

    private array $oroVersions = [];
    private string $composerConfigFile;
    private string $projectRoot;
    private InputInterface $input;
    private SymfonyStyle $io;

    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                CommandOption::ORO_VERSIONS,
                null,
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Oro versions list',
                '42,51,60'
            )
            ->addOption(CommandOption::COMPOSER_CONFIG, null, InputArgument::OPTIONAL, 'Composer config filename', 'composer.json')
            ->addOption(CommandOption::SOURCE, null, InputArgument::OPTIONAL, 'Directory to be processed', 'src')
            ->addOption(CommandOption::DRY_RUN, null, InputOption::VALUE_NONE, 'Only see the diff of changes, do not save them to files')
            ->addOption(CommandOption::CLEAR_CACHE, null, InputOption::VALUE_NONE, 'Clear unchanged files cache')
            ->addOption(CommandOption::DEBUG, null, InputOption::VALUE_NONE, 'Display debug output')
            ->addOption(CommandOption::XDEBUG, null, InputOption::VALUE_NONE, 'Display xdebug output');
        // @codingStandardsIgnoreStart
        $this->setHelp(
            <<<'HELP'
The <info>%command.name%</info> command is the application upgrade tool. 
It processes most of the PHP changes in order to upgrade the application to the defined Oro version.
It doesn’t upgrade YAML, Twig, JS, or SCSS.
In most cases, the command can be used without any options.

  <info>php %command.full_name%</info>

The <info>--oro-versions</info> option can be used to specify the oro versions list needed upgrade to:
By default, inspections related to v4.2 and higher will be applied.

    <info>php %command.full_name% --oro-versions=51</info>
    will apply inspections related to the Oro v5.1
    
    <info>php %command.full_name% --oro-versions=51,60</info>
    will apply inspections related to Oro versions in provided order.
    First - for v5.1, and after that v6.0

The <info>--composer-config</info> option can be used to specify composer configuration file name:
It can be usefully when the composer config name is different from 'composer.json'
E.G.: 'dev.json'

    <info>php %command.full_name% --composer-config=dev.json</info>

The <info>--source</info> option can be used to specify the directory of the application to be processed:
It can be usefully when the source root directory name is different from 'src'
or it is needed to process a part of application. (E.g.: Newly added bundle, etc.)

    <info>php %command.full_name% --source=scr//Oro//Bundle//NewBundle</info>
    will apply inspections to the PHP files placed in the src/Oro/Bundle/NewBundle directory recursively

The <info>--dry-run</info> option can be used to list the diff of changes without applying them:

    <info>php %command.full_name% --dry-run</info>

The <info>--clear-cache</info> option can be used to clear unchanged files cache:

    <info>php %command.full_name% --clear-cache</info>

The <info>--debug</info> option is used to display debug output:

    <info>php %command.full_name% --debug</info>

The <info>--xdebug</info> option is used to display xdebug output:
Usefully while debugging the Rector inspections

    <info>php %command.full_name% --xdebug</info>

Please see below an example with the most common usage flow:
1. Check the list of changes that will be applied

    <info>php %command.full_name% --dry-run</info>
 
 2. Apply changes

    <info>php %command.full_name% --clear-cache</info>

HELP
        );

        $this
            ->addUsage('--oro-versions=<versions>')
            ->addUsage('--composer-config=<composer.json>')
            ->addUsage('--source=<sourceroot>')
            ->addUsage('--dry-run')
            ->addUsage('--clear-cache')
            ->addUsage('--debug')
            ->addUsage('--xdebug');
        // @codingStandardsIgnoreEnd
        parent::configure();
    }

    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);
        $this->projectRoot = realpath(self::BASE_PATH);
        $this->oroVersions = explode(',', $this->input->getOption(CommandOption::ORO_VERSIONS));
        $this->composerConfigFile = $this->input->getOption(CommandOption::COMPOSER_CONFIG);
    }

    /**
     * @throws \Throwable
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Stage 1: Perform all needed checkups
        $this->io->title('Init … ');
        $this->io->writeln('<info>Checking source roots … </info>');
        if (!$this->checkSourceRoots($this->projectRoot, $this->composerConfigFile)) {
            return Command::FAILURE;
        }

        $this->io->writeln('<info>Getting composer class map … </info>');
        $classMap = $this->getClassMap($this->projectRoot);

        $this->io->writeln('<info>Finding source classes … </info>');
        $sourceListManipulator = new SourceListManipulator(
            $classMap,
            $this->projectRoot,
            $this->composerConfigFile
        );

        $parentClasses = $sourceListManipulator->getParentClassesList();

        // Stage 2: Generate signatures dump to the tmp file
        $this->io->writeln('<info>Generating signatures dump … </info>');
        $signatureBuilder = new SignatureBuilder();
        $signatures = $signatureBuilder->build($parentClasses);

        if (count($signatures, COUNT_RECURSIVE) < 4) {
            $this->io->warning(
                [
                    'Signatures were not dumped.',
                    'Please, ensure that "composer dump-autoload -o" was performed correctly.',
                    'The command will proceed execution,',
                    'but inspections based on the signature dump will be skipped',
                ]
            );
        } else {
            $this->io->writeln('<info>Signatures list was generated</info>');
        }

        $this->writeSignatureFile($signatures);
        // Stage 3: Apply Rector inspections
        $this->io->title('Starting Rector inspections … ');
        $this->runRector();
        // Stage 4: Delete tmp files. Provide hints/reports
        $this->deleteSignatureFile();
        $this->showFinalMessage();

        return Command::SUCCESS;
    }

    private function runRector()
    {
        $rector = new RectorRunner($this->projectRoot);

        foreach ($this->oroVersions as $oroVersion) {
            $configPath = $this->projectRoot . sprintf('/vendor/oro/upgrade-toolkit/sets/oro-%s.php', $oroVersion);

            if (!file_exists($configPath)) {
                $this->io->warning(
                    [
                        sprintf('Rector configuration for Oro v.%s is not found.', $oroVersion),
                        'Skipped.'
                    ]
                );
                continue;
            }

            $parameters = [
                $this->input->getOption(CommandOption::SOURCE),
                '--config=' . $configPath,
            ];
            !$this->input->getOption(CommandOption::CLEAR_CACHE) ?: $parameters[] = '--clear-cache';
            !$this->input->getOption(CommandOption::DRY_RUN) ?: $parameters[] = '--dry-run';
            !$this->input->getOption(CommandOption::DEBUG) ?: $parameters[] = '--debug';
            !$this->input->getOption(CommandOption::XDEBUG) ?: $parameters[] = '--xdebug';

            $exitCode = $rector->process($parameters, $this->io);

            if (Command::FAILURE === $exitCode) {
                $this->io->warning(
                    [
                        sprintf(
                            'bin/rector process %s was finished with an error',
                            implode(' ', $parameters)
                        ),
                        'Some inspections can be applied in a wrong way because of this'
                    ]
                );
            }
        }
    }

    private function getSignaturesListFile(): string
    {
        return sys_get_temp_dir() . '/' . SignatureConfig::FILE_NAME;
    }

    private function writeSignatureFile(array $signatures): void
    {
        $filePath = $this->getSignaturesListFile();
        $export = VarExporter::export($signatures);

        if ($this->input->getOption(CommandOption::DEBUG)) {
            $this->io->block('Dumped Signatures list:', 'DEBUG MODE', 'fg=black;bg=yellow', ' ', true);
            $this->io->write($export);
        }

        $fileContent = <<<PHP
<?php

return $export;

PHP;

        FileSystem::write($filePath, $fileContent);
    }

    private function deleteSignatureFile(): void
    {
        $filePath = $this->getSignaturesListFile();
        if (file_exists($filePath)) {
            FileSystem::delete($filePath);
        }
    }

    private function getClassMap(string $projectRoot): ?array
    {
        $classMap = [];
        // Run 'composer dump-autoload -o' first
        $finder = new ExecutableFinder();
        $composer = $finder->find('composer');

        if (!$composer) {
            $this->io->warning('Composer executable is not found.');

            return $classMap;
        }

        $command = [$composer, 'dump-autoload', '-o'];
        $composerProcess = new Process($command);
        $this->io->writeln(sprintf('<info>Running %s … </info>', $composerProcess->getCommandLine()));

        $io = $this->io;
        $composerProcess->run(
            function ($type, $buffer) use ($io) {
                $io->write($buffer);
            }
        );

        if (!$composerProcess->isSuccessful()) {
            $this->io->warning('Command "composer dump-autoload -o" is failed');

            return $classMap;
        }

        // Get class-map directly from the file because ClassLoader can`t access to updated map while the app is running
        $autoloadClassmap = $projectRoot . '/vendor/composer/autoload_classmap.php';
        if (!file_exists($autoloadClassmap)) {
            $this->io->warning(sprintf('File %s was not found.', $autoloadClassmap));

            return $classMap;
        }

        $classMap = require $autoloadClassmap;

        return $classMap;
    }

    private function checkSourceRoots(string $projectRoot, string $composerConfigFile): bool
    {
        $sourceRootsStatus = false;
        // Check composer config
        $composerConfigPath = $projectRoot . '/' . $composerConfigFile;
        if (!file_exists($composerConfigPath)) {
            $this->io->error(sprintf('No %s file exists at %s', $composerConfigFile, $projectRoot));

            return $sourceRootsStatus;
        }

        // Ensure that autoload configuration is defined
        $composerConfig = json_decode(Filesystem::read($composerConfigPath), true);
        if (array_key_exists('psr-4', $composerConfig['autoload'])) {
            $sourceRootsStatus = true;
            $this->io->writeln(sprintf('<info>PSR-4 autoload roots defined in %s</info>', $composerConfigPath));
        } else {
            $this->io->error(sprintf('No PSR-4 autoload roots defined in %s', $composerConfigPath));
        }

        return $sourceRootsStatus;
    }

    private function showFinalMessage(): void
    {
        $message = [
            sprintf(
                '<info>The %s command has been processed the <comment>%s</comment> directory.</info>',
                $this->getName(),
                $this->input->getOption(CommandOption::SOURCE)
            )
        ];

        if ($this->input->getOption(CommandOption::DRY_RUN)) {
            $message = array_merge(
                $message,
                [
                    sprintf(
                        '<info>The command was performed with <comment>--%s</comment> option</info>',
                        CommandOption::DRY_RUN
                    ),
                    sprintf(
                        '<info>To apply the results re-run the command with the <comment>--%s</comment> option</info>',
                        CommandOption::CLEAR_CACHE
                    ),
                ]
            );
        } else {
            $message = array_merge(
                $message,
                [
                    '<info>Ensure that applied changes are relevant and proceed with upgrade.',
                    // To Do: Change the link after the updated doc will be ready
                    'Visit <comment>https://doc.oroinc.com/backend/setup/upgrade-to-new-version/</comment> for more information.</info>',
                    sprintf(
                        '<info>Otherwise fix the issues and re-run the command with <comment>--%s</comment> option</info>',
                        CommandOption::CLEAR_CACHE
                    )
                ]
            );
        }

        $this->io->writeln($message);
    }
}
