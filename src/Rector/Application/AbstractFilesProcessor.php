<?php

namespace Oro\UpgradeToolkit\Rector\Application;

use Nette\Utils\FileSystem;

/**
 * Provides methods to manipulate with temporary file
 * The file is used to store the list of files needed to be processed
 */
class AbstractFilesProcessor
{
    protected const TMP_FILE = 'TMP_FILE';

    private string $tmpFilePath;

    public function __construct()
    {
        $this->tmpFilePath = sys_get_temp_dir() . '/' . $this::TMP_FILE;
    }

    public function deleteTmpFile(): void
    {
        FileSystem::delete($this->tmpFilePath);
    }

    protected function readTmpFile(): array
    {
        $content = [];
        if (is_file($this->tmpFilePath)) {
            $content = FileSystem::read($this->tmpFilePath);
            $content = unserialize($content);
        }

        return $content;
    }

    protected function writeTmpFile(string $filePath, string $fileContent = ''): void
    {
        $content = [];
        if (is_file($this->tmpFilePath)) {
            $content = unserialize(FileSystem::read($this->tmpFilePath));
        }

        $content[$filePath] = $fileContent;
        FileSystem::write($this->tmpFilePath, serialize($content));
    }
}
