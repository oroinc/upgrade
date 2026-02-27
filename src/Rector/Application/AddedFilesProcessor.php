<?php

namespace Oro\UpgradeToolkit\Rector\Application;

use Nette\Utils\FileSystem;
use Rector\Console\Style\RectorStyle;
use Rector\ValueObject\Configuration;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Adds listed files
 * @see \Oro\UpgradeToolkit\Rector\Console\Command\FileOperationsCommand
 */
class AddedFilesProcessor extends AbstractFilesProcessor
{
    protected const TMP_FILE = 'FILES_TO_ADD';

    /**
     * @param RectorStyle|SymfonyStyle|null $io
     */
    public function __construct(private $io = null)
    {
        parent::__construct();
    }

    public function process(Configuration $configuration): void
    {
        foreach ($this->getFilesToAdd() as $filePath => $fileContent) {
            $message = sprintf('File "%s" will be added', $filePath);
            if (!$configuration->isDryRun()) {
                FileSystem::write($filePath, $fileContent);
                $message = sprintf('File "%s" was added', $filePath);
            }

            $this->io?->note($message);
        }
    }

    public function addFileToAdd(string $filePath, string $fileContent): void
    {
        $this->writeTmpFile($filePath, $fileContent);
    }

    public function getFilesToAdd(): array
    {
        return $this->readTmpFile();
    }
}
