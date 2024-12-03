<?php

namespace Oro\UpgradeToolkit\YmlFixer\Manipilator;

use Nette\Utils\FileSystem;
use Oro\UpgradeToolkit\YmlFixer\ValueObject\YmlDefinition;

/**
 * YmlFileSourceManipulator allows one to do needed actions with .yml files
 */
class YmlFileSourceManipulator
{
    public function checkYmlFile(string $filePath): YmlDefinition
    {
        $ymlDefinition = $this->getYmlDefinition($filePath);
        if (empty($ymlDefinition->getErrors())) {
            try {
                $sourceManipulator = new YamlSourceManipulator($ymlDefinition->getStringDefinition());
                $sourceManipulator->setData($sourceManipulator->getData());
            } catch (\Throwable $e) {
                $ymlDefinition->setError($e);
            }
        }

        return $ymlDefinition;
    }

    public function getYmlDefinition($filePath): YmlDefinition
    {
        $stringDefinition = null;
        $arrayDefinition = null;
        $content = null;
        $error = null;

        try {
            $content = FileSystem::read($filePath);
        } catch (\Throwable $exception) {
            $error = $exception;
        }


        if ($content) {
            try {
                $sourceManipulator = new YamlSourceManipulator($content);
                $stringDefinition = $sourceManipulator->getContents();
                $arrayDefinition = $sourceManipulator->getData();
            } catch (\Throwable $exception) {
                $error = $exception;
            }
        } else {
            $error = new \Exception(
                message: sprintf('File is empty: %s', $filePath)
            );
        }

        $def = new YmlDefinition(
            filePath: $filePath,
            stringDefinition: $stringDefinition,
            arrayDefinition: $arrayDefinition,
        );

        if ($error) {
            $def->setError($error);
        }

        return $def;
    }

    public function setUpdatedYmlContent(YmlDefinition $ymlDefinition): YmlDefinition
    {
        $sourceManipulator = new YamlSourceManipulator($ymlDefinition->getStringDefinition());
        $sourceManipulator->setData($ymlDefinition->getArrayDefinition());

        $ymlDefinition->setUpdatedStringDefinition($sourceManipulator->getContents());

        return $ymlDefinition;
    }
}
