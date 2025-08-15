<?php

namespace Oro\UpgradeToolkit\Rector\Signature;

/**
 * Calculates and provides a coverage score report for detected classes and their relationships.
 *
 * Provides methods to calculate and retrieve a coverage score report.
 * The score is based on the ratio of autoloaded parent classes to
 * extended classes detected via reflection.
 */
final class CoverageScoreCounter
{
    public const MINIMUM_COVERAGE_LEVEL = 60;

    public static ?int $totalDetectedClasses = null;
    public static ?int $extendedClassesCount = null;
    public static ?int $autoloadedParentClassesCount = null;
    public static array $nonAutoloadedParentClassesList = [];

    public static function getCoverageReport(): ?array
    {
        $coverageScore = self::calculateCoverageScore();

        if (null !== $coverageScore) {
            return [
                'coverage_score' => $coverageScore,
                'extended_classes_count' => self::$extendedClassesCount,
                'autoloaded_parent_classes_count' => self::$autoloadedParentClassesCount,
                'total_classes_checked' => self::$totalDetectedClasses,
            ];
        }

        return null;
    }

    private static function calculateCoverageScore(): int|float|null
    {
        if (self::$extendedClassesCount && self::$autoloadedParentClassesCount) {
            $score = (self::$autoloadedParentClassesCount / self::$extendedClassesCount) * 100;

            return round($score, 1);
        }

        return null;
    }
}
