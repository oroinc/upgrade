<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Tests\Semadiff\Support;

trait TempDirTrait
{
    private string $tmpDir;

    protected function setUpTempDir(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/php-semadiff-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDownTempDir(): void
    {
        if (!is_dir($this->tmpDir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($this->tmpDir);
    }

    private function createFile(string $relativePath, string $content): void
    {
        $fullPath = $this->tmpDir . '/' . $relativePath;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($fullPath, $content);
    }
}
