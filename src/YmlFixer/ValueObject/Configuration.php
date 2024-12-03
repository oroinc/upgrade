<?php

namespace Oro\UpgradeToolkit\YmlFixer\ValueObject;

/**
 * Contains all needed configuration data
 */
class Configuration
{
    public function __construct(
        public string $source,
        public bool $isDryRun = false,
        public bool $isDebug = false,
        public bool $showProgressBar = true,
        public array $paths = [],
    ) {
    }
}
