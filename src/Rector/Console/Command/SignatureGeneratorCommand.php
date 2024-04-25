<?php

namespace Oro\Rector\Console\Command;

use Composer\Autoload\ClassLoader;
use Nette\Utils\FileSystem;
use Oro\Rector\Signature\SignatureBuilder;
use Oro\Rector\Signature\SignatureConfig;
use Oro\Rector\Signature\SignatureDiffer;
use ReflectionException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use Symfony\Component\VarExporter\VarExporter;

/**
 * Command that generates signature listing
 * @inspired https://github.com/craftcms/rector
 */
#[AsCommand(
    name: 'signature:generate',
    description: 'Generates signature listing by the src directory autoload mapping (by default) or by the provided namespace list',
    aliases: ['s:g'],
    hidden: false
)]
class SignatureGeneratorCommand extends Command
{
    private const BASE_PATH = '.';
    private InputInterface $input;
    private SymfonyStyle $io;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption(
                'exclude-namespaces',
                'e',
                InputArgument::OPTIONAL,
                'Namespaces that should be excluded from the signature listing (separated by a comma).
                 E.g.: Name\\\Space\\\One,Name\\\Space\\\Two,Etc'
            )
            ->addOption(
                'namespaces',
                'l',
                InputArgument::OPTIONAL,
                'Namespaces that should be listed (separated by a comma).
                 If the option is defined, namespaces listed as option value will be listed.
                 All other namespaces will be ignored.
                 E.g.: Name\\\Space\\\One,Name\\\Space\\\Two,Etc'
            )
            ->addOption('use-vendor', null, InputOption::VALUE_NONE, 'Include source classes placed in the vendor dir')
            ->addOption('diff', null, InputOption::VALUE_NONE, 'Generate signatures diffs')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Generate signatures without saving them to the file');
    }

    /**
     * @throws ReflectionException
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Initializing … ');
        $sourceRoots = $this->getSourceRoots();
        if (!$sourceRoots) {
            return Command::FAILURE;
        }

        $classLoader = $this->getClassLoader();
        if (!$classLoader) {
            return Command::FAILURE;
        }

        $diff = $this->input->getOption('diff');
        if ($diff) {
            $this->io->title('Generating signatures diffs … ');
            $signaturesListFile = $this->getSignaturesListFile();
            $oldSignatures = require $signaturesListFile;

            $signatureDiffer = new SignatureDiffer();
            $signatures = $signatureDiffer->diff($oldSignatures);
            $this->writeSignatureFile($signatures);
            $this->io->success('DONE');

            return Command::SUCCESS;
        }

        $this->io->title('Finding source classes … ');
        $srcClasses = $this->getSrcClasses($classLoader, $sourceRoots);
        $srcClasses = $this->excludeNamespaces($srcClasses);
        $srcClasses = $this->includeNamespaces($srcClasses);
        // Generate signatures
        $signatureBuilder = new SignatureBuilder();
        $signatures = $signatureBuilder->build($srcClasses);

        if (count($signatures, COUNT_RECURSIVE) < 4) {
            $message = [
                'Signatures were not detected',
                'Please, check the options you set ',
                'and ensure that "composer dump-autoload -o" was performed before run this command',
            ];
            $this->io->warning($message);

            return Command::FAILURE;
        }

        $this->writeSignatureFile($signatures);
        $this->io->success('DONE');

        return Command::SUCCESS;
    }

    private function getSignaturesListFile(): string
    {
        return sys_get_temp_dir() . '/' . SignatureConfig::FILE_NAME;
    }

    private function getSourceRoots(): ?array
    {
        $sourceRoots = null;
        // Check composer config
        $composerConfigPath = realpath(self::BASE_PATH) . '/composer.json';
        if (!file_exists($composerConfigPath)) {
            $composerConfigPath = self::BASE_PATH . '/dev.json';
        }

        if (file_exists($composerConfigPath)) {
            // Ensure that autoload configuration is defined
            $composerConfig = json_decode(Filesystem::read($composerConfigPath), true);
            if (array_key_exists('psr-4', $composerConfig['autoload'])) {
                $this->io->success(sprintf('PSR-4 autoload roots defined in %s', $composerConfigPath));
                // Get source roots
                foreach ($composerConfig['autoload']['psr-4'] as $namespace => $namespaceBasePath) {
                    $namespace = rtrim($namespace, '\\') . '\\';
                    $namespaceBasePath = realpath($namespaceBasePath);
                    if ($namespaceBasePath) {
                        $sourceRoots[$namespace] = $namespaceBasePath;
                    }
                }
            } else {
                $this->io->error(sprintf('No PSR-4 autoload roots defined in %s', $composerConfigPath));
            }
        } else {
            $this->io->error(sprintf('No composer.json/dev.json file exists at %s', realpath(self::BASE_PATH)));
        }

        return $sourceRoots;
    }

    private function getClassLoader(): ?ClassLoader
    {
        $classLoader = null;
        $autoloadClass = null;
        foreach (get_declared_classes() as $class) {
            if (str_starts_with($class, 'ComposerAutoloaderInit')) {
                $autoloadClass = $class;
                break;
            }
        }

        if ($autoloadClass) {
            $this->io->success('Class loader is detected');
            $classLoader = $autoloadClass::getLoader();
        } else {
            $this->io->error('Class loader is not detected');
        }

        return $classLoader;
    }

    private function getSrcClasses(ClassLoader $classLoader, array $sourceRoots): array
    {
        $srcClasses = [];
        $vendorDir = realpath(self::BASE_PATH . '/vendor');

        $useVendor = $this->input->getOption('use-vendor');
        if ($useVendor) {
            foreach ($classLoader->getClassMap() as $class => $file) {
                // Exclude AppKernel
                if (str_starts_with($class, 'AppKernel')) {
                    continue;
                }
                $srcClasses[] = $class;
            }
        } else {
            foreach ($classLoader->getClassMap() as $class => $file) {
                // Exclude AppKernel
                if (str_starts_with($class, 'AppKernel')) {
                    continue;
                }
                $file = realpath($file);
                // Ignore everything in vendor
                if (str_starts_with($file, $vendorDir)) {
                    continue;
                }
                // Make sure it's in one of the source roots
                foreach ($sourceRoots as $namespace => $nsBasePath) {
                    if (str_starts_with($file, $nsBasePath)) {
                        $srcClasses[] = $class;
                    }
                }
            }
        }

        return $srcClasses;
    }

    private function excludeNamespaces(array $srcClasses): array
    {
        $excludedNamespaces = $this->input->getOption('exclude-namespaces');
        if ($excludedNamespaces) {
            $excludedNamespaces = explode(',', $excludedNamespaces);
            foreach ($srcClasses as $key => $class) {
                foreach ($excludedNamespaces as $namespace) {
                    if (str_starts_with($class, $namespace)) {
                        unset($srcClasses[$key]);
                    }
                }
            }
        }

        return $srcClasses;
    }

    private function includeNamespaces(array $srcClasses): array
    {
        $result = [];

        $includedNamespaces = $this->input->getOption('namespaces');
        if ($includedNamespaces) {
            $includedNamespaces = explode(',', $includedNamespaces);
            foreach ($srcClasses as $class) {
                foreach ($includedNamespaces as $namespace) {
                    if (str_starts_with($class, $namespace)) {
                        $result[] = $class;
                    }
                }
            }
        }

        return $result;
    }

    private function writeSignatureFile(array $signatures): void
    {
        $filePath = $this->getSignaturesListFile();
        $export = VarExporter::export($signatures);

        $dryRun = $this->input->getOption('dry-run');
        if ($dryRun) {
            $this->io->block('Signatures list that will be generated:', 'DRY RUN MODE', 'fg=black;bg=yellow', ' ', true);
            $this->io->write($export);
        } else {
            $fileContent = <<<PHP
<?php

return $export;

PHP;

            FileSystem::write($filePath, $fileContent);
            $this->io->info('Signatures list was generated');
        }
    }
}
