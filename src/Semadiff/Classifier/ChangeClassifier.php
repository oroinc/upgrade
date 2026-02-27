<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Classifier;

use Oro\UpgradeToolkit\Semadiff\Comparator\FileComparisonResult;

final class ChangeClassifier
{
    public const COSMETIC = 'cosmetic';
    public const SIGNATURE = 'signature';
    public const LOGIC = 'logic';

    public function classify(FileComparisonResult $result): string
    {
        // Highest risk wins
        if ($result->classStructureChanged || $result->bodyChanged || $result->membersAddedOrRemoved) {
            return self::LOGIC;
        }

        if ($result->signatureChanged) {
            return self::SIGNATURE;
        }

        return self::COSMETIC;
    }
}
