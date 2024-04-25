<?php

namespace Oro\Rector\Application;

use Nette\Utils\FileSystem;
use Rector\Console\Style\RectorStyle;
use Rector\ValueObject\Configuration;

/**
 * Adds listed files
 * @see \Oro\Rector\Application\ApplicationFileProcessor
 */
class AddedFilesProcessor extends AbstractFilesProcessor
{
    protected const TMP_FILE = 'FILES_TO_ADD';

    public function __construct(private readonly RectorStyle $rectorStyle)
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
            $this->rectorStyle->note($message);
        }
    }

    public function addFileToAdd(string $filePath, string $fileContent): void
    {
        $this->writeTmpFile($filePath, $fileContent);
    }

    private function getFilesToAdd(): array
    {
        return $this->readTmpFile();
    }
}
