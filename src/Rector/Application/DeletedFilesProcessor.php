<?php

namespace Oro\UpgradeToolkit\Rector\Application;

use Nette\Utils\FileSystem;
use Rector\Console\Style\RectorStyle;
use Rector\ValueObject\Configuration;
use RectorPrefix202602\Webmozart\Assert\Assert;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Removes listed files
 * @see \Oro\UpgradeToolkit\Rector\Console\Command\FileOperationsCommand
 */
class DeletedFilesProcessor extends AbstractFilesProcessor
{
    protected const TMP_FILE = 'FILES_TO_DELETE';

    /**
     * @param RectorStyle|SymfonyStyle|null $io
     */
    public function __construct(private $io = null)
    {
        parent::__construct();
    }

    public function process(Configuration $configuration): void
    {
        foreach ($this->getFilesToDelete() as $filePath => $fileContent) {
            $message = sprintf('File "%s" will be deleted', $filePath);
            if (!$configuration->isDryRun()) {
                FileSystem::delete($filePath);
                $message = sprintf('File "%s" was deleted', $filePath);
            }

            $this->io?->note($message);
        }
    }

    public function addFileToDelete(string $filePath): void
    {
        Assert::file($filePath);
        $this->writeTmpFile($filePath);
    }

    public function getFilesToDelete(): array
    {
        return $this->readTmpFile();
    }
}
