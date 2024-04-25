<?php

namespace Oro\Rector\Application;

use Nette\Utils\FileSystem;
use Rector\Console\Style\RectorStyle;
use Rector\ValueObject\Configuration;
use RectorPrefix202403\Webmozart\Assert\Assert;

/**
 * Removes listed files
 * @see \Oro\Rector\Application\ApplicationFileProcessor
 */
class DeletedFilesProcessor extends AbstractFilesProcessor
{
    protected const TMP_FILE = 'FILES_TO_DELETE';

    public function __construct(private readonly RectorStyle $rectorStyle)
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
            $this->rectorStyle->note($message);
        }
    }

    public function addFileToDelete(string $filePath): void
    {
        Assert::file($filePath);
        $this->writeTmpFile($filePath);
    }

    private function getFilesToDelete(): array
    {
        return $this->readTmpFile();
    }
}
