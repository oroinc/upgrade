<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Cli\Report;

final class MarkdownReportWriter
{
    public function write(ReportData $data): void
    {
        $md = [];

        // ── Summary ──────────────────────────────────────────────────────
        $md[] = '# Upgrade Analysis Report';
        $md[] = '';
        $md[] = '## Summary';
        $md[] = sprintf('- Files analyzed: %d', $data->filesAnalyzed);
        if ($data->runBc) {
            $remaining = $data->bcResults['totalItems'] - $data->bcResults['resolvedItems'];
            $md[] = sprintf('- **BC items: %d**', $remaining);
            $md[] = sprintf('- Signature changes: %d (%d affect project)', count($data->signatureInfo), count($data->bcAllDeps));
            $md[] = sprintf('- Removed members: %d (%d used by project)', count($data->removedMembers), $this->countRemovedMemberRefs($data->bcResults));
            $md[] = sprintf('- Deleted classes: %d (%d referenced by project)', count($data->deletedFqcns), count($data->deletedRefs));
        }
        if ($data->runLogic) {
            $md[] = sprintf('- **Logic changes: %d** (%d with dependents)', count($data->fqcnInfo), count($data->fqcnDependents));
        }
        $md[] = '';

        // ── TOC ──────────────────────────────────────────────────────────
        $hasBcToc = $data->runBc && ($data->bcResults['vendorItems'] !== [] || $data->bcResults['deletedItems'] !== []);
        $hasLogicToc = $data->runLogic && $data->fqcnDependents !== [];
        if ($hasBcToc || $hasLogicToc) {
            $md[] = '<details>';
            $md[] = '<summary><h2>Contents</h2></summary>';
            $md[] = '';
        }
        if ($data->runBc) {
            $this->writeBcContents($md, $data->bcResults);
        }
        if ($data->runLogic) {
            $this->writeLogicContents($md, $data->fqcnDependents);
        }
        if ($hasBcToc || $hasLogicToc) {
            $md[] = '</details>';
            $md[] = '';
        }

        // ── BC-Breaking Changes ──────────────────────────────────────────
        if ($data->runBc) {
            $this->writeBcSection($md, $data->bcResults, $data->bcAllDeps, $data->changes);
            $this->writeDeletedClassesSection($md, $data->bcResults);
        }

        // ── Logic Changes ────────────────────────────────────────────────
        if ($data->runLogic) {
            $this->writeLogicSection($md, $data->fqcnInfo, $data->fqcnDependents, $data->changes);
        }

        $dir = dirname($data->outputFile);
        if ($dir !== '.' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($data->outputFile, implode("\n", $md) . "\n");
    }

    // ── TOC ─────────────────────────────────────────────────────────────

    /**
     * @param string[] $md
     * @param array<string, array{extends: string[], implements: string[], traits: string[]}> $fqcnDependents
     */
    private function writeLogicContents(array &$md, array $fqcnDependents): void
    {
        if ($fqcnDependents === []) {
            return;
        }

        $fqcns = array_keys($fqcnDependents);
        sort($fqcns);

        /** @var list<array{fqcn: string, anchor: string, label: string, shortName: string}> */
        $entries = [];

        foreach ($fqcns as $fqcn) {
            $deps = $fqcnDependents[$fqcn];
            $depCount = count($deps['extends']) + count($deps['implements']) + count($deps['traits']);
            $anchor = $this->fqcnToAnchor($fqcn);

            $lastSlash = strrpos($fqcn, '\\');
            $shortName = $lastSlash !== false ? substr($fqcn, $lastSlash + 1) : $fqcn;

            $entries[] = [
                'fqcn' => $fqcn,
                'anchor' => $anchor,
                'label' => sprintf('%d dependents', $depCount),
                'shortName' => $shortName,
            ];
        }

        $md[] = '**[Logic Changes](#logic-changes)**';
        $md[] = '';

        $this->renderTocTree($md, $entries);
    }

    /**
     * @param string[] $md
     * @param array{vendorItems: array<string, array{details: string[], items: list<array<string, mixed>>}>, deletedItems: array<string, array{refs: list<array{depFqcn: string, resolved: bool}>}>, totalItems: int, resolvedItems: int} $bcResults
     */
    private function writeBcContents(array &$md, array $bcResults): void
    {
        $vendorItems = $bcResults['vendorItems'];
        $deletedItems = $bcResults['deletedItems'];

        if ($vendorItems === [] && $deletedItems === []) {
            return;
        }

        $md[] = '**[BC-Breaking Changes](#bc-breaking-changes)**';
        $md[] = '';

        $fqcns = array_keys($vendorItems);
        sort($fqcns);

        /** @var list<array{fqcn: string, anchor: string, label: string, shortName: string}> */
        $entries = [];

        foreach ($fqcns as $fqcn) {
            $info = $vendorItems[$fqcn];
            $items = $info['items'];
            $totalItems = count($items);
            $resolvedCount = 0;
            foreach ($items as $item) {
                if (isset($item['resolved']) && $item['resolved'] === true) {
                    $resolvedCount++;
                }
            }

            $allResolved = $resolvedCount === $totalItems && $totalItems > 0;

            if ($allResolved) {
                continue;
            }

            $unresolvedCount = $totalItems - $resolvedCount;

            $anchor = 'bc-' . $this->fqcnToAnchor($fqcn);

            $lastSlash = strrpos($fqcn, '\\');
            $shortName = $lastSlash !== false ? substr($fqcn, $lastSlash + 1) : $fqcn;

            $label = $totalItems > 0
                ? sprintf('%d affected', $unresolvedCount)
                : 'dependents';

            $entries[] = [
                'fqcn' => $fqcn,
                'anchor' => $anchor,
                'label' => $label,
                'shortName' => $shortName,
            ];
        }

        $this->renderTocTree($md, $entries);

        if ($deletedItems !== []) {
            $md[] = sprintf('- [Deleted Classes](#deleted-classes-still-referenced) — %d classes', count($deletedItems));
            $md[] = '';
        }
    }

    // ── Logic section writer ─────────────────────────────────────────────

    /**
     * @param string[] $md
     * @param array<string, array{details: string[], path: string}> $fqcnInfo
     * @param array<string, array{extends: string[], implements: string[], traits: string[]}> $fqcnDependents
     * @param array<string, string> $changes
     */
    private function writeLogicSection(
        array &$md,
        array $fqcnInfo,
        array $fqcnDependents,
        array $changes,
    ): void {
        if ($fqcnDependents === []) {
            return;
        }

        $md[] = '## Logic Changes';
        $md[] = '';

        $classLevelLabels = ['Class structure changed', 'Class made final', 'Classes added', 'Classes removed'];

        foreach ($fqcnDependents as $fqcn => $deps) {
            $anchor = $this->fqcnToAnchor($fqcn);
            $md[] = sprintf('<a id="%s"></a>', $anchor);
            $md[] = '';
            $md[] = sprintf('### `%s`', $fqcn);
            $md[] = '';

            if (isset($fqcnInfo[$fqcn])) {
                $grouped = $this->groupDetails($fqcnInfo[$fqcn]['details']);
                foreach ($grouped as $label => $members) {
                    if (in_array($label, $classLevelLabels, true)) {
                        $md[] = sprintf('- **%s**', $label);
                    } elseif (count($members) > 5) {
                        $md[] = sprintf('- **%s:**', $label);
                        foreach ($members as $member) {
                            $md[] = sprintf('  - `%s`', $member);
                        }
                    } else {
                        $md[] = sprintf('- **%s:** %s', $label, implode(', ', array_map(
                            static fn (string $member) => '`' . $member . '`',
                            $members,
                        )));
                    }
                }
                $md[] = '';
            }

            if ($deps['extends'] !== []) {
                $md[] = sprintf('**Extended by (%d):**', count($deps['extends']));
                foreach ($deps['extends'] as $dep) {
                    $md[] = sprintf('- [ ] `%s`', $dep);
                }
                $md[] = '';
            }

            if ($deps['implements'] !== []) {
                $md[] = sprintf('**Implemented by (%d):**', count($deps['implements']));
                foreach ($deps['implements'] as $dep) {
                    $md[] = sprintf('- [ ] `%s`', $dep);
                }
                $md[] = '';
            }

            if ($deps['traits'] !== []) {
                $md[] = sprintf('**Used as trait by (%d):**', count($deps['traits']));
                foreach ($deps['traits'] as $dep) {
                    $md[] = sprintf('- [ ] `%s`', $dep);
                }
                $md[] = '';
            }

            if (isset($changes[$fqcn])) {
                $this->renderChangesBlock($md, $changes[$fqcn]);
            }

            $md[] = '---';
            $md[] = '';
        }
    }

    // ── BC section writer ───────────────────────────────────────────────

    /**
     * @param string[] $md
     * @param array{vendorItems: array<string, array{details: string[], items: list<array<string, mixed>>}>, deletedItems: array<string, array{refs: list<array{depFqcn: string, resolved: bool}>}>, totalItems: int, resolvedItems: int} $bcResults
     * @param array<string, array{extends: string[], implements: string[], traits: string[], uses: string[]}> $bcAllDeps
     * @param array<string, string> $changes
     */
    private function writeBcSection(
        array &$md,
        array $bcResults,
        array $bcAllDeps,
        array $changes,
    ): void {
        $vendorItems = $bcResults['vendorItems'];

        if ($vendorItems === []) {
            return;
        }

        $md[] = '<a id="bc-breaking-changes"></a>';
        $md[] = '';
        $md[] = '## BC-Breaking Changes';
        $md[] = '';

        $fqcns = array_keys($vendorItems);
        sort($fqcns);

        foreach ($fqcns as $fqcn) {
            $info = $vendorItems[$fqcn];
            $items = $info['items'];
            $details = $info['details'];

            $totalItems = count($items);
            $resolvedCount = 0;
            foreach ($items as $item) {
                if (isset($item['resolved']) && $item['resolved'] === true) {
                    $resolvedCount++;
                }
            }
            $allResolved = $resolvedCount === $totalItems && $totalItems > 0;

            if ($allResolved) {
                continue;
            }

            $anchor = 'bc-' . $this->fqcnToAnchor($fqcn);
            $md[] = sprintf('<a id="%s"></a>', $anchor);
            $md[] = '';

            $md[] = sprintf('### `%s`', $fqcn);
            $md[] = '';

            // BC-specific detail grouping
            $grouped = $this->groupBcDetails($details);
            foreach ($grouped as $label => $members) {
                if ($members === []) {
                    $md[] = sprintf('- **%s**', $label);
                } elseif (count($members) > 5) {
                    $md[] = sprintf('- **%s:**', $label);
                    foreach ($members as $member) {
                        $md[] = sprintf('  - `%s`', $member);
                    }
                } else {
                    $md[] = sprintf('- **%s:** %s', $label, implode(', ', array_map(
                        static fn (string $member) => '`' . $member . '`',
                        $members,
                    )));
                }
            }
            $md[] = '';

            // Unresolved items only
            /** @var list<array<string, mixed>> */
            $unresolved = array_values(array_filter($items, static fn (array $item) => !isset($item['resolved']) || $item['resolved'] !== true));

            if ($unresolved !== []) {
                $md[] = '**Update required:**';

                // Group by depFqcn + method
                /** @var array<string, array{types: string[], paramDiff: string}> */
                $grouped = [];
                foreach ($unresolved as $item) {
                    $depFqcn = is_string($item['depFqcn'] ?? null) ? $item['depFqcn'] : '';
                    $method = is_string($item['method'] ?? null) ? $item['method'] : '';
                    $paramDiff = is_string($item['paramDiff'] ?? null) ? $item['paramDiff'] : '';
                    $type = is_string($item['type'] ?? null) ? $item['type'] : '';

                    $key = $depFqcn . '::' . $method;
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = ['types' => [], 'paramDiff' => ''];
                    }
                    $grouped[$key]['types'][] = $this->resolutionTypeLabel($type);
                    if ($paramDiff !== '' && $grouped[$key]['paramDiff'] === '') {
                        $grouped[$key]['paramDiff'] = $paramDiff;
                    }
                }

                foreach ($grouped as $key => $group) {
                    [$depFqcn, $method] = explode('::', $key, 2);
                    $typeText = implode(' and ', array_unique($group['types']));
                    $md[] = sprintf('- [ ] `%s` — %s `%s`', $depFqcn, $typeText, $method);

                    if ($group['paramDiff'] !== '') {
                        $md[] = '  ```diff';
                        foreach (explode("\n", $group['paramDiff']) as $line) {
                            $md[] = '  ' . $line;
                        }
                        $md[] = '  ```';
                    }
                }
                $md[] = '';
            }

            // Typed dependents (broader than resolution items)
            $deps = $bcAllDeps[$fqcn] ?? null;
            if ($deps !== null) {
                $resolvedFqcns = [];
                foreach ($items as $item) {
                    if (isset($item['depFqcn']) && is_string($item['depFqcn'])) {
                        $resolvedFqcns[$item['depFqcn']] = true;
                    }
                }

                $this->writeBcDependents($md, $deps, $resolvedFqcns);
            }

            // Changes block
            if (isset($changes[$fqcn])) {
                $this->renderChangesBlock($md, $changes[$fqcn]);
            }

            $md[] = '---';
            $md[] = '';
        }
    }

    /**
     * @param string[] $md
     * @param array{extends: string[], implements: string[], traits: string[], uses: string[]} $deps
     * @param array<string, bool> $excludeFqcns FQCNs already covered by resolution items
     */
    private function writeBcDependents(array &$md, array $deps, array $excludeFqcns): void
    {
        $remainingExtends = array_filter($deps['extends'], static fn (string $dep) => !isset($excludeFqcns[$dep]));
        $remainingImplements = array_filter($deps['implements'], static fn (string $dep) => !isset($excludeFqcns[$dep]));
        $remainingTraits = array_filter($deps['traits'], static fn (string $dep) => !isset($excludeFqcns[$dep]));
        $remainingUses = array_filter($deps['uses'], static fn (string $dep) => !isset($excludeFqcns[$dep]));

        $total = count($remainingExtends) + count($remainingImplements)
            + count($remainingTraits) + count($remainingUses);

        if ($total === 0) {
            return;
        }

        $md[] = sprintf('**Dependents (%d):**', $total);
        $md[] = '';

        if ($remainingExtends !== []) {
            $md[] = sprintf('_Extended by (%d):_', count($remainingExtends));
            foreach ($remainingExtends as $dep) {
                $md[] = sprintf('- [ ] `%s`', $dep);
            }
            $md[] = '';
        }

        if ($remainingImplements !== []) {
            $md[] = sprintf('_Implemented by (%d):_', count($remainingImplements));
            foreach ($remainingImplements as $dep) {
                $md[] = sprintf('- [ ] `%s`', $dep);
            }
            $md[] = '';
        }

        if ($remainingTraits !== []) {
            $md[] = sprintf('_Used as trait by (%d):_', count($remainingTraits));
            foreach ($remainingTraits as $dep) {
                $md[] = sprintf('- [ ] `%s`', $dep);
            }
            $md[] = '';
        }

        if ($remainingUses !== []) {
            $md[] = sprintf('_Referenced by (%d):_', count($remainingUses));
            foreach ($remainingUses as $dep) {
                $md[] = sprintf('- [ ] `%s`', $dep);
            }
            $md[] = '';
        }
    }

    /**
     * @param string[] $md
     * @param array{vendorItems: array<string, array{details: string[], items: list<array<string, mixed>>}>, deletedItems: array<string, array{refs: list<array{depFqcn: string, resolved: bool}>}>, totalItems: int, resolvedItems: int} $bcResults
     */
    private function writeDeletedClassesSection(array &$md, array $bcResults): void
    {
        $deletedItems = $bcResults['deletedItems'];
        if ($deletedItems === []) {
            return;
        }

        $md[] = '<a id="deleted-classes-still-referenced"></a>';
        $md[] = '';
        $md[] = '### Deleted Classes (Still Referenced)';
        $md[] = '';

        $fqcns = array_keys($deletedItems);
        sort($fqcns);

        foreach ($fqcns as $fqcn) {
            $md[] = sprintf('#### `%s`', $fqcn);
            foreach ($deletedItems[$fqcn]['refs'] as $ref) {
                $checkbox = $ref['resolved'] ? '[x]' : '[ ]';
                $md[] = sprintf('- %s `%s`', $checkbox, $ref['depFqcn']);
            }
            $md[] = '';
        }
    }

    // ── BC detail grouping ──────────────────────────────────────────────

    /**
     * Group BC detail strings by change type for the BC section.
     *
     * @param string[] $details
     * @return array<string, string[]> group label => member names (empty array for class-level)
     */
    private function groupBcDetails(array $details): array
    {
        $map = [
            'Class made final'             => 'Class made final',
            'Class structure changed'      => 'Class structure changed',
            'Constructor changed'          => '__CONSTRUCTOR__',
            'Method made final'            => 'Methods made final',
            'Method made abstract'         => 'Methods made abstract',
            'Method return type changed'   => 'Method return types changed',
            'Method visibility tightened'  => 'Method visibility tightened',
            'Method param added (required)' => 'Method params added (required)',
            'Method param removed'         => 'Method params removed',
            'Method param type changed'    => 'Method param types changed',
            'Method param renamed'         => 'Method params renamed',
            'Method param modifier changed' => 'Method param modifiers changed',
            'Method static changed'        => 'Method static changed',
            'Method removed'               => 'Methods removed',
            'Property removed'             => 'Properties removed',
            'Property type changed'        => 'Property types changed',
            'Property made readonly'       => 'Properties made readonly',
            'Property visibility tightened' => 'Property visibility tightened',
            'Constant made final'          => 'Constants made final',
            'Constant type changed'        => 'Constant types changed',
            'Constant visibility changed'  => 'Constant visibility changed',
            'Constant removed'             => 'Constants removed',
        ];

        $classLevelLabels = ['Class made final', 'Class structure changed'];

        $groups = [];
        $constructorAnnotations = [];
        $ungrouped = [];

        foreach ($details as $detail) {
            $matched = false;
            foreach ($map as $prefix => $label) {
                if (str_starts_with($detail, $prefix . ': ')) {
                    $member = substr($detail, strlen($prefix) + 2);
                    $colonPos = strpos($member, '::');
                    if ($colonPos !== false) {
                        $member = substr($member, $colonPos + 2);
                    }

                    if ($label === '__CONSTRUCTOR__') {
                        // Collect constructor-related annotations from other details
                        $matched = true;
                        break;
                    }

                    // Track constructor-related param changes for annotation
                    if ($member === '__construct') {
                        $constructorAnnotations[] = $this->constructorAnnotation($prefix);
                        $matched = true;
                        break;
                    }

                    if (in_array($label, $classLevelLabels, true)) {
                        $groups[$label] = [];
                    } else {
                        $groups[$label][] = $member;
                    }
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $ungrouped[] = $detail;
            }
        }

        // Merge constructor annotations
        $hasConstructor = false;
        foreach ($details as $detail) {
            if (str_starts_with($detail, 'Constructor changed: ')) {
                $hasConstructor = true;
                break;
            }
        }

        if ($hasConstructor) {
            $annotation = $constructorAnnotations !== []
                ? ' _(' . implode(', ', array_unique($constructorAnnotations)) . ')_'
                : '';
            $groups = ['Constructor changed' . $annotation => []] + $groups;
        }

        // Deduplicate within groups
        foreach ($groups as $label => $members) {
            $groups[$label] = array_values(array_unique($members));
        }

        if ($ungrouped !== []) {
            $groups['Other'] = $ungrouped;
        }

        return $groups;
    }

    private function constructorAnnotation(string $prefix): string
    {
        return match ($prefix) {
            'Method param added (required)' => 'required params added',
            'Method param removed' => 'params removed',
            'Method param type changed' => 'param types changed',
            'Method param renamed' => 'params renamed',
            'Method visibility tightened' => 'visibility tightened',
            default => 'signature changed',
        };
    }

    private function resolutionTypeLabel(string $type): string
    {
        return match ($type) {
            'method_override' => 'overrides',
            'interface_impl' => 'implements',
            'parent_call' => 'calls parent::',
            'instance_call' => 'calls',
            'static_call' => 'calls static',
            'removed_member' => 'references',
            default => $type,
        };
    }

    // ── Detail grouping ──────────────────────────────────────────────────

    /**
     * Group detail strings by change type, stripping the class name prefix for compactness.
     *
     * @param string[] $details
     * @return array<string, string[]> group label => member names
     */
    private function groupDetails(array $details): array
    {
        $map = [
            'Method body changed'              => 'Methods modified',
            'Method return type changed'       => 'Method signatures changed',
            'Method return type added'         => 'Method type annotations added',
            'Method visibility tightened'      => 'Method signatures changed',
            'Method visibility loosened'        => 'Method visibility loosened',
            'Method made abstract'             => 'Method signatures changed',
            'Method made final'                => 'Methods made final',
            'Method static changed'            => 'Method signatures changed',
            'Method param added (required)'    => 'Method signatures changed',
            'Method param added (optional)'    => 'Method type annotations added',
            'Method param removed'             => 'Method signatures changed',
            'Method param type changed'        => 'Method signatures changed',
            'Method param type added'          => 'Method type annotations added',
            'Method param renamed'             => 'Method signatures changed',
            'Method param modifier changed'    => 'Method signatures changed',
            'Constructor changed'              => 'Constructors changed',
            'Method added'                     => 'Methods added',
            'Method removed'                   => 'Methods removed',
            'Property type changed'            => 'Property signatures changed',
            'Property type added'              => 'Property type annotations added',
            'Property visibility tightened'    => 'Property signatures changed',
            'Property visibility loosened'      => 'Property visibility loosened',
            'Property made readonly'           => 'Property signatures changed',
            'Property static changed'          => 'Property signatures changed',
            'Property default value changed'   => 'Properties modified',
            'Property added'                   => 'Properties added',
            'Property removed'                 => 'Properties removed',
            'Constant made final'              => 'Constants made final',
            'Constant type changed'            => 'Constant types changed',
            'Constant visibility changed'      => 'Constant signatures changed',
            'Constant value changed'           => 'Constants modified',
            'Constant added'                   => 'Constants added',
            'Constant removed'                 => 'Constants removed',
            'Class made final'                 => 'Class made final',
            'Class structure changed'          => 'Class structure changed',
            'Class added'                      => 'Classes added',
            'Class removed'                    => 'Classes removed',
        ];

        $groups = [];
        $ungrouped = [];

        foreach ($details as $detail) {
            $matched = false;
            foreach ($map as $prefix => $label) {
                if (str_starts_with($detail, $prefix . ': ')) {
                    $member = substr($detail, strlen($prefix) + 2);
                    // Strip ClassName:: prefix, keep just the member name
                    $colonPos = strpos($member, '::');
                    if ($colonPos !== false) {
                        $member = substr($member, $colonPos + 2);
                    }
                    $groups[$label][] = $member;
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $ungrouped[] = $detail;
            }
        }

        // Merge body+signature changes for same method into a single "modified" entry
        if (isset($groups['Methods modified']) && isset($groups['Method signatures changed'])) {
            $bodyOnly = array_diff($groups['Methods modified'], $groups['Method signatures changed']);
            $sigOnly = array_diff($groups['Method signatures changed'], $groups['Methods modified']);
            $both = array_intersect($groups['Methods modified'], $groups['Method signatures changed']);

            $groups['Methods modified'] = array_merge($both, $bodyOnly);

            if ($sigOnly === []) {
                unset($groups['Method signatures changed']);
            } else {
                $groups['Method signatures changed'] = array_values($sigOnly);
            }
        }

        // Deduplicate within groups
        foreach ($groups as $label => $members) {
            $groups[$label] = array_values(array_unique($members));
        }

        if ($ungrouped !== []) {
            $groups['Other'] = $ungrouped;
        }

        return $groups;
    }

    // ── TOC tree ─────────────────────────────────────────────────────────

    /**
     * Render a 3-level TOC tree: vendor/package → namespace → class entries.
     *
     * @param string[] $md
     * @param list<array{fqcn: string, anchor: string, label: string, shortName: string}> $entries
     */
    private function renderTocTree(array &$md, array $entries): void
    {
        /** @var array<string, array<string, list<array{anchor: string, label: string, shortName: string}>>> */
        $tree = [];

        foreach ($entries as $entry) {
            $parts = explode('\\', $entry['fqcn']);

            if (count($parts) < 3) {
                $vendor = $parts[0];
                $ns = '';
            } else {
                $vendor = $parts[0] . '/' . $parts[1];
                $nsParts = array_slice($parts, 2, -1);
                $ns = $nsParts !== [] ? implode('\\', $nsParts) : '';
            }

            $tree[$vendor][$ns][] = [
                'anchor' => $entry['anchor'],
                'label' => $entry['label'],
                'shortName' => $entry['shortName'],
            ];
        }
        ksort($tree);

        foreach ($tree as $vendor => $namespaces) {
            ksort($namespaces);
            $md[] = sprintf('_%s_', $vendor);
            $md[] = '';
            foreach ($namespaces as $ns => $items) {
                if ($ns !== '') {
                    $md[] = sprintf('_%s_', $ns);
                }
                foreach ($items as $item) {
                    $md[] = sprintf('- [`%s`](#%s) — %s', $item['shortName'], $item['anchor'], $item['label']);
                }
                $md[] = '';
            }
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Render a changes block: commits list + diff in a collapsible <details>.
     *
     * @param string[] $md
     */
    private function renderChangesBlock(array &$md, string $content): void
    {
        $md[] = '<details>';
        $md[] = '<summary>Changes</summary>';
        $md[] = '';

        $parts = explode('<!-- DIFF -->', $content, 2);
        $commitPart = trim($parts[0]);
        $diffPart = isset($parts[1]) ? trim($parts[1]) : '';

        if ($commitPart !== '') {
            $md[] = '_Commits:_';
            foreach (explode("\n", $commitPart) as $line) {
                $md[] = $line;
            }
            $md[] = '';
        }

        if ($diffPart !== '') {
            $md[] = '```diff';
            $md[] = $diffPart;
            $md[] = '```';
            $md[] = '';
        }

        $md[] = '</details>';
        $md[] = '';
    }

    private function fqcnToAnchor(string $fqcn): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $fqcn));
    }

    /**
     * Count how many removed-member FQCNs have dependents in the results.
     *
     * @param array{vendorItems: array<string, array{details: string[], items: list<array<string, mixed>>}>, deletedItems: array<string, array{refs: list<array{depFqcn: string, resolved: bool}>}>, totalItems: int, resolvedItems: int} $bcResults
     */
    private function countRemovedMemberRefs(array $bcResults): int
    {
        $count = 0;
        foreach ($bcResults['vendorItems'] as $info) {
            foreach ($info['items'] as $item) {
                if (($item['type'] ?? '') === 'removed_member') {
                    $count++;
                }
            }
        }

        return $count;
    }
}
