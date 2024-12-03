<?php

namespace Oro\UpgradeToolkit\YmlFixer\Contract;

/**
 * YmlFixer Rules interface
 */
interface YmlFixerInterface
{
    public function fix(array &$config): void;

    /**
     * Glob pattern to match the file.
     */
    public function matchFile(): string;
}
