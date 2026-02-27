<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Cli\Report;

final readonly class ReportData
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param array<string, array{details: string[], path: string}> $fqcnInfo
     * @param array<string, array{extends: string[], implements: string[], traits: string[]}> $fqcnDependents
     * @param array<string, string> $changes
     * @param array<string, array{details: string[], path: string, changedMethods: string[], constructorChanged: bool}> $signatureInfo
     * @param array<string, string[]> $removedMembers
     * @param string[] $deletedFqcns
     * @param array<string, array{extends: string[], implements: string[], traits: string[], uses: string[]}> $bcAllDeps
     * @param array<string, string[]> $deletedRefs
     * @param array{vendorItems: array<string, array{details: string[], items: list<array<string, mixed>>}>, deletedItems: array<string, array{refs: list<array{depFqcn: string, resolved: bool}>}>, totalItems: int, resolvedItems: int} $bcResults
     */
    public function __construct(
        public string $outputFile,
        public int $filesAnalyzed,
        public bool $runLogic,
        public bool $runBc,
        public array $fqcnInfo,
        public array $fqcnDependents,
        public array $changes,
        public array $signatureInfo,
        public array $removedMembers,
        public array $deletedFqcns,
        public array $bcAllDeps,
        public array $deletedRefs,
        public array $bcResults,
    ) {
    }
}
