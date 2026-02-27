<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Comparator;

final class FileComparisonResult
{
    /**
     * @param string[] $details
     * @param string[] $parseErrors Parse error/warning messages
     */
    public function __construct(
        public readonly bool $classStructureChanged,
        public readonly bool $signatureChanged,
        public readonly bool $bodyChanged,
        public readonly bool $membersAddedOrRemoved,
        public readonly array $details = [],
        public readonly array $parseErrors = [],
    ) {
    }
}
