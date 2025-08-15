<?php

namespace Oro\UpgradeToolkit\Rector\Signature;

use Nette\Utils\FileSystem;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\Reflection\ReflectionProvider;

/**
 * The class contains methods needed to manipulate the lists of the classes included in the psr-4 root dirs.
 */
final class SourceListManipulator
{
    private ReflectionProvider $reflectionProvider;
    private ?array $vendorClassesList = null;

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
        $containerFactory = new ContainerFactory('');
        $tmpDir = sys_get_temp_dir();
        $container = $containerFactory->create($tmpDir, [], []);

        $this->reflectionProvider = $container->getByType(ReflectionProvider::class);
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
                // Ignore everything in vendor
                if (file_exists($file) && str_starts_with($file, $vendorDir)) {
                    continue;
                }
                $file = realpath($file);
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

    public function getParentClassesList(): array
    {
        $srcClasses = $this->getSourceClassesList();
        $this->updateTotalDetectedClassesCount($srcClasses);
        $parentClasses = [];

        foreach ($srcClasses as $class) {
            $parentClassName = $this->extractParentClassName($class);
            if ($parentClassName) {
                $this->updateExtendedClassesCount();

                if ($this->isVendorClass($parentClassName)) {
                    $parentClasses[] = $parentClassName;
                    $this->updateAutoloadedParentClassesCount();
                } else {
                    $this->trackNonAutoloadedParentClass($class, $parentClassName);
                }
            }
        }

        return array_unique($parentClasses);
    }

    public function getVendorClassesList(): array
    {
        if ($this->vendorClassesList) {
            return $this->vendorClassesList;
        }

        $vendorDir = $this->basePath . '/vendor';

        foreach ($this->classMap as $class => $file) {
            // Get everything in vendor
            if (file_exists($file)) {
                if (str_starts_with($file, $vendorDir)) {
                    $this->vendorClassesList[] = $class;
                }
            } else {
                $file = realpath($file);
                if (str_starts_with($file, $vendorDir)) {
                    $this->vendorClassesList[] = $class;
                }
            }
        }

        return $this->vendorClassesList;
    }

    private function isVendorClass(string $className): bool
    {
        return in_array($className, $this->getVendorClassesList(), true);
    }

    private function extractParentClassName(string $class): ?string
    {
        if (!$this->reflectionProvider->hasClass($class)) {
            // Handle non-reflectable class
            echo sprintf('Cannot reflect the class: %s Skipped … ' . PHP_EOL, $class);

            return null;
        }

        $reflection = $this->reflectionProvider->getClass($class);
        $parentClass = $reflection->getParentClass();

        return $parentClass ? $parentClass->getName() : null;
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

    private function updateTotalDetectedClassesCount(array $srcClasses): void
    {
        CoverageScoreCounter::$totalDetectedClasses = count($srcClasses);
    }

    private function updateExtendedClassesCount(): void
    {
        CoverageScoreCounter::$extendedClassesCount++;
    }

    private function updateAutoloadedParentClassesCount(): void
    {
        CoverageScoreCounter::$autoloadedParentClassesCount++;
    }

    private function trackNonAutoloadedParentClass(string $class, string $parentClassName): void
    {
        CoverageScoreCounter::$nonAutoloadedParentClassesList[] = [$class, $parentClassName];
    }
}
