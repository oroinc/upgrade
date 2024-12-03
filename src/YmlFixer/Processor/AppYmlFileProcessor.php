<?php

namespace Oro\UpgradeToolkit\YmlFixer\Processor;

use Oro\UpgradeToolkit\YmlFixer\Manipilator\YmlFileSourceManipulator;
use Oro\UpgradeToolkit\YmlFixer\Printer\YmlPrinter;
use Oro\UpgradeToolkit\YmlFixer\ValueObject\Configuration;
use Oro\UpgradeToolkit\YmlFixer\ValueObject\YmlDefinition;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * AppYmlFileProcessor needed to run process the .yml files
 */
class AppYmlFileProcessor
{
    public function run(Configuration $configuration, SymfonyStyle $io): array
    {
        $filePaths = $configuration->paths;
        if ($filePaths === []) {
            return [];
        }

        $processResults = $this->processFiles($filePaths, $io);
        $this->applyChanges($processResults);

        if ($configuration->isDryRun) {
            return $processResults;
        }
        $this->printYml($processResults);

        return $processResults;
    }

    private function processFiles($filePaths, SymfonyStyle $io): array
    {
        $fileCount = \count($filePaths);
        $io->progressStart($fileCount);
        $io->progressAdvance(0);
        $postFileCallback = function (int $stepCount) use ($io): void {
            $io->progressAdvance($stepCount);
        };

        $fileProcessor = new YmlFileProcessor();

        return $fileProcessor->processFiles($filePaths, $postFileCallback);
    }

    private function applyChanges(array &$ymlDefinitionsArray): void
    {
        /** @var YmlDefinition $ymlDefinition */
        foreach ($ymlDefinitionsArray as $key => $ymlDefinition) {
            if (null !== $ymlDefinition->getArrayDefinition()) {
                $sourceManipulator = new YmlFileSourceManipulator();
                try {
                    $ymlDefinition = $sourceManipulator->setUpdatedYmlContent($ymlDefinition);
                } catch (YamlManipulationFailedException $exception) {
                    $ymlDefinition->setError($exception);
                }
            }

            $ymlDefinitionsArray[$key] = $ymlDefinition;
        }
    }

    private function printYml(array &$ymlDefinitionsArray): void
    {
        $printer = new YmlPrinter();
        /** @var YmlDefinition $ymlDefinition */
        foreach ($ymlDefinitionsArray as $ymlDefinition) {
            if ($ymlDefinition->isUpdated() && empty($ymlDefinition->getErrors())) {
                $printer->printYml($ymlDefinition);
            }
        }
    }
}
