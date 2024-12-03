<?php

namespace Oro\UpgradeToolkit\YmlFixer\Printer;

use Nette\Utils\FileSystem;
use Oro\UpgradeToolkit\YmlFixer\ValueObject\YmlDefinition;

/**
 * Allows one to write needed changes to the .yml files
 */
class YmlPrinter
{
    public function printYml(YmlDefinition $ymlDefinition): void
    {
        FileSystem::write($ymlDefinition->getFilePath(), $ymlDefinition->getUpdatedStringDefinition());
    }
}
