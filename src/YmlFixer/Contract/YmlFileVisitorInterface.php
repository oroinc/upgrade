<?php

namespace Oro\UpgradeToolkit\YmlFixer\Contract;

use Oro\UpgradeToolkit\YmlFixer\ValueObject\YmlDefinition;

/**
 * YmlVisitor`s interface
 */
interface YmlFileVisitorInterface
{
    public function visit(string $filePath): YmlDefinition;
}
