<?php

namespace Oro\UpgradeToolkit\Rector\Signature;

use Nette\Utils\FileSystem;

/**
 * The class contains methods needed to manipulate the lists of the classes included in the psr-4 root dirs.
 */
final class SourceListManipulator
{
    /**
     * $classMap - An array that contains an autoload classmap. E.G.: @see vendor/composer/autoload_classmap.php
     * $basePath - Absolute path to the project root dir. E.G.: '/home/projects/projectName'
     * $composerConfigFile - Composer config filename. 'composer.json' by default.
     */
    public function __construct(
        private readonly array $classMap,
        private string $basePath,
        private string $composerConfigFile = 'composer.json'
    ) {
    }

    public function getSourceClassesList(): array
    {
        $srcClasses = [];
        $vendorDir = $this->basePath . '/vendor';
        $sourceRoots = $this->getSourceRoots();

        if ($sourceRoots) {
            foreach ($this->classMap as $class => $file) {
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

    /**
     * @throws \ReflectionException
     */
    public function getParentClassesList(): array
    {
        $srcClasses = $this->getSourceClassesList();
        $vendorClasses = $this->getVendorClassesList();
        $parentClasses = [];
        $classes = [];

        foreach ($srcClasses as $class) {
            $reflection = new \ReflectionClass($class);

            $parentClass = $reflection->getParentClass() ?: null;
            if ($parentClass) {
                $classes[] = $parentClass->getName();
            }
        }

        // Exclude parents located in the src dir
        foreach (array_unique($classes) as $class) {
            if (in_array($class, $vendorClasses)) {
                $parentClasses[] = $class;
            }
        }

        return array_unique($parentClasses);
    }

    public function getVendorClassesList(): array
    {
        $vendorClasses = [];
        $vendorDir = $this->basePath . '/vendor';

        foreach ($this->classMap as $class => $file) {
            $file = realpath($file);
            // Get everything in vendor
            if (str_starts_with($file, $vendorDir)) {
                $vendorClasses[] = $class;
            }
        }

        return $vendorClasses;
    }

    private function getSourceRoots(): ?array
    {
        $sourceRoots = null;
        // Get composer config
        $composerConfigPath = $this->basePath . '/' . $this->composerConfigFile;
        if (!file_exists($composerConfigPath)) {
            return $sourceRoots;
        }

        // Get source roots
        $composerConfig = json_decode(Filesystem::read($composerConfigPath), true);
        foreach ($composerConfig['autoload']['psr-4'] as $namespace => $namespaceBasePath) {
            $namespace = rtrim($namespace, '\\') . '\\';
            $namespaceBasePath = realpath($namespaceBasePath);
            if ($namespaceBasePath) {
                $sourceRoots[$namespace] = $namespaceBasePath;
            }
        }

        return $sourceRoots;
    }
}
