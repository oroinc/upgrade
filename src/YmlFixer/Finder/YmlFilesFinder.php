<?php

namespace Oro\UpgradeToolkit\YmlFixer\Finder;

use Symfony\Component\Finder\Finder;

/**
 * YmlFilesFinder allows one to find needed .yml files
 */
class YmlFilesFinder
{
    private const YAML_GLOB_PATTERN = '*.yml';

    public function findAllYmlFiles(string $searchDir): array
    {
        return $this->find($searchDir, self::YAML_GLOB_PATTERN);
    }

    public function find(
        string $searchDir,
        string $filename,
        ?string $pathPattern = null,
        ?string $contentPattern = null
    ): array {
        $result = [];
        $searchDir = realpath('./' . $searchDir);

        if ($searchDir) {
            $finder = new Finder();
            $finder->files()
                ->in($searchDir)
                ->followLinks()
                ->name($filename);

            if ($pathPattern) {
                $finder->path($pathPattern);
            }

            if ($contentPattern) {
                $finder->contains($contentPattern);
            }

            foreach ($finder as $file) {
                $result[] = $file->getRealPath();
            }
        }

        return $result;
    }
}
