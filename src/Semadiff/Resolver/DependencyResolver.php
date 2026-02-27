<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Resolver;

use FilesystemIterator;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Oro\UpgradeToolkit\Semadiff\FqcnPathMap;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class DependencyResolver
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    public const TYPE_EXTENDS = 'extends';
    public const TYPE_IMPLEMENTS = 'implements';
    public const TYPE_TRAITS = 'traits';
    public const TYPE_USES = 'uses';

    public const ALL_TYPES = [
        self::TYPE_EXTENDS,
        self::TYPE_IMPLEMENTS,
        self::TYPE_TRAITS,
        self::TYPE_USES,
    ];

    /**
     * Find all classes in $directory that extend, implement, use (trait), or
     * reference (general use) any of the given FQCNs.
     * Results are grouped by the target FQCN.
     *
     * @param string      $directory   Directory to scan
     * @param string[]    $targetFqcns FQCNs to search for as parents/interfaces/traits/references
     * @param string[]|null $types     Dependency types to collect (null = all). Use TYPE_* constants.
     */
    public function findDependents(string $directory, array $targetFqcns, ?array $types = null): DependencyResult
    {
        $targetSet = [];
        foreach ($targetFqcns as $fqcn) {
            $targetSet[ltrim($fqcn, '\\')] = true;
        }

        $enabledTypes = $types !== null ? array_flip($types) : array_flip(self::ALL_TYPES);

        /** @var array<string, string[]> */
        $extends = [];
        /** @var array<string, string[]> */
        $implements = [];
        /** @var array<string, string[]> */
        $traits = [];
        /** @var array<string, string[]> */
        $uses = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $code = file_get_contents($file->getPathname());
            if ($code === false) {
                continue;
            }

            try {
                $ast = $this->parser->parse($code);
                if ($ast === null) {
                    continue;
                }

                $traverser = new NodeTraverser();
                $traverser->addVisitor(new NameResolver());
                $ast = $traverser->traverse($ast);
            } catch (\Throwable $e) {
                continue;
            }

            $this->collectDependents($ast, $targetSet, $enabledTypes, $extends, $implements, $traits, $uses);
        }

        $extends = $this->sortGrouped($extends);
        $implements = $this->sortGrouped($implements);
        $traits = $this->sortGrouped($traits);
        $uses = $this->sortGrouped($uses);

        return new DependencyResult($extends, $implements, $traits, $uses);
    }

    /**
     * Run multiple dependency queries in a single directory scan, capturing FQCN→path map.
     *
     * @param string $directory Directory to scan
     * @param array<string, array{fqcns: string[], types: string[]|null}> $queries Named queries
     * @return array{results: array<string, DependencyResult>, pathMap: FqcnPathMap}
     */
    public function findDependentsMulti(string $directory, array $queries): array
    {
        // Build per-query target sets and type filters
        /** @var array<string, array<string, bool>> $targetSets */
        $targetSets = [];
        /** @var array<string, array<string, int>> $typeSets */
        $typeSets = [];
        /** @var array<string, bool> $unionTargets */
        $unionTargets = [];
        /** @var array<string, int> $unionTypes */
        $unionTypes = [];

        foreach ($queries as $name => $query) {
            $targetSets[$name] = [];
            foreach ($query['fqcns'] as $fqcn) {
                $normalized = ltrim($fqcn, '\\');
                $targetSets[$name][$normalized] = true;
                $unionTargets[$normalized] = true;
            }
            $types = $query['types'];
            $typeSets[$name] = $types !== null ? array_flip($types) : array_flip(self::ALL_TYPES);
            foreach (array_keys($typeSets[$name]) as $type) {
                $unionTypes[$type] = 0;
            }
        }

        // Initialize per-query result accumulators
        /** @var array<string, array{extends: array<string, string[]>, implements: array<string, string[]>, traits: array<string, string[]>, uses: array<string, string[]>}> $acc */
        $acc = [];
        foreach (array_keys($queries) as $name) {
            $acc[$name] = ['extends' => [], 'implements' => [], 'traits' => [], 'uses' => []];
        }

        $pathMap = new FqcnPathMap();

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $code = file_get_contents($file->getPathname());
            if ($code === false) {
                continue;
            }

            try {
                $ast = $this->parser->parse($code);
                if ($ast === null) {
                    continue;
                }

                $traverser = new NodeTraverser();
                $traverser->addVisitor(new NameResolver());
                $ast = $traverser->traverse($ast);
            } catch (\Throwable $e) {
                continue;
            }

            // Capture FQCN→path and collect dependents using the union target set
            $this->collectMultiDependents(
                $ast,
                $unionTargets,
                $unionTypes,
                $targetSets,
                $typeSets,
                $acc,
                $pathMap,
                $file->getPathname(),
            );
        }

        // Build results
        $results = [];
        foreach (array_keys($queries) as $name) {
            $results[$name] = new DependencyResult(
                $this->sortGrouped($acc[$name]['extends']),
                $this->sortGrouped($acc[$name]['implements']),
                $this->sortGrouped($acc[$name]['traits']),
                $this->sortGrouped($acc[$name]['uses']),
            );
        }

        return ['results' => $results, 'pathMap' => $pathMap];
    }

    /**
     * @param Node[] $stmts
     * @param array<string, bool> $unionTargets
     * @param array<string, int> $unionTypes
     * @param array<string, array<string, bool>> $targetSets
     * @param array<string, array<string, int>> $typeSets
     * @param array<string, array{extends: array<string, string[]>, implements: array<string, string[]>, traits: array<string, string[]>, uses: array<string, string[]>}> $acc
     */
    private function collectMultiDependents(
        array $stmts,
        array $unionTargets,
        array $unionTypes,
        array $targetSets,
        array $typeSets,
        array &$acc,
        FqcnPathMap $pathMap,
        string $filePath,
    ): void {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Stmt\Namespace_ && $stmt->stmts !== null) {
                $this->collectMultiDependents($stmt->stmts, $unionTargets, $unionTypes, $targetSets, $typeSets, $acc, $pathMap, $filePath);
                continue;
            }

            if (!($stmt instanceof Stmt\ClassLike) || $stmt->name === null) {
                continue;
            }

            $fqcn = $this->resolveFqcn($stmt);
            $pathMap->set($fqcn, $filePath);

            // Collect extends
            if (isset($unionTypes[self::TYPE_EXTENDS])) {
                if ($stmt instanceof Stmt\Class_ && $stmt->extends !== null) {
                    $parentFqcn = ltrim($stmt->extends->toString(), '\\');
                    if (isset($unionTargets[$parentFqcn])) {
                        foreach ($acc as $name => &$data) {
                            if (isset($typeSets[$name][self::TYPE_EXTENDS]) && isset($targetSets[$name][$parentFqcn])) {
                                $data['extends'][$parentFqcn][] = $fqcn;
                            }
                        }
                        unset($data);
                    }
                }
                if ($stmt instanceof Stmt\Interface_ && $stmt->extends !== []) {
                    foreach ($stmt->extends as $ext) {
                        $extFqcn = ltrim($ext->toString(), '\\');
                        if (isset($unionTargets[$extFqcn])) {
                            foreach ($acc as $name => &$data) {
                                if (isset($typeSets[$name][self::TYPE_EXTENDS]) && isset($targetSets[$name][$extFqcn])) {
                                    $data['extends'][$extFqcn][] = $fqcn;
                                }
                            }
                            unset($data);
                        }
                    }
                }
            }

            // Collect implements
            if (isset($unionTypes[self::TYPE_IMPLEMENTS])) {
                if (($stmt instanceof Stmt\Class_ || $stmt instanceof Stmt\Enum_) && $stmt->implements !== []) {
                    foreach ($stmt->implements as $iface) {
                        $ifaceFqcn = ltrim($iface->toString(), '\\');
                        if (isset($unionTargets[$ifaceFqcn])) {
                            foreach ($acc as $name => &$data) {
                                if (isset($typeSets[$name][self::TYPE_IMPLEMENTS]) && isset($targetSets[$name][$ifaceFqcn])) {
                                    $data['implements'][$ifaceFqcn][] = $fqcn;
                                }
                            }
                            unset($data);
                        }
                    }
                }
            }

            // Collect trait uses
            if (isset($unionTypes[self::TYPE_TRAITS])) {
                foreach ($stmt->stmts as $member) {
                    if ($member instanceof Stmt\TraitUse) {
                        foreach ($member->traits as $trait) {
                            $traitFqcn = ltrim($trait->toString(), '\\');
                            if (isset($unionTargets[$traitFqcn])) {
                                foreach ($acc as $name => &$data) {
                                    if (isset($typeSets[$name][self::TYPE_TRAITS]) && isset($targetSets[$name][$traitFqcn])) {
                                        $data['traits'][$traitFqcn][] = $fqcn;
                                    }
                                }
                                unset($data);
                            }
                        }
                    }
                }
            }

            // Collect general usage
            if (isset($unionTypes[self::TYPE_USES])) {
                $usedTargets = [];
                foreach ($stmt->stmts as $member) {
                    if ($member instanceof Stmt\TraitUse) {
                        continue;
                    }
                    $this->findNameReferences($member, $unionTargets, $usedTargets);
                }
                foreach (array_keys($usedTargets) as $target) {
                    foreach ($acc as $name => &$data) {
                        if (isset($typeSets[$name][self::TYPE_USES]) && isset($targetSets[$name][$target])) {
                            $data['uses'][$target][] = $fqcn;
                        }
                    }
                    unset($data);
                }
            }
        }
    }

    /**
     * @param Node[] $stmts
     * @param array<string, bool> $targetSet
     * @param array<string, int>  $enabledTypes
     * @param array<string, string[]> $extends
     * @param array<string, string[]> $implements
     * @param array<string, string[]> $traits
     * @param array<string, string[]> $uses
     */
    private function collectDependents(
        array $stmts,
        array $targetSet,
        array $enabledTypes,
        array &$extends,
        array &$implements,
        array &$traits,
        array &$uses,
    ): void {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Stmt\Namespace_ && $stmt->stmts !== null) {
                $this->collectDependents($stmt->stmts, $targetSet, $enabledTypes, $extends, $implements, $traits, $uses);
                continue;
            }

            if (!($stmt instanceof Stmt\ClassLike) || $stmt->name === null) {
                continue;
            }

            $fqcn = $this->resolveFqcn($stmt);

            // Check extends (class or interface)
            if (isset($enabledTypes[self::TYPE_EXTENDS])) {
                if ($stmt instanceof Stmt\Class_ && $stmt->extends !== null) {
                    $parentFqcn = ltrim($stmt->extends->toString(), '\\');
                    if (isset($targetSet[$parentFqcn])) {
                        $extends[$parentFqcn][] = $fqcn;
                    }
                }

                if ($stmt instanceof Stmt\Interface_ && $stmt->extends !== []) {
                    foreach ($stmt->extends as $ext) {
                        $extFqcn = ltrim($ext->toString(), '\\');
                        if (isset($targetSet[$extFqcn])) {
                            $extends[$extFqcn][] = $fqcn;
                        }
                    }
                }
            }

            // Check implements
            if (isset($enabledTypes[self::TYPE_IMPLEMENTS])) {
                if (($stmt instanceof Stmt\Class_ || $stmt instanceof Stmt\Enum_) && $stmt->implements !== []) {
                    foreach ($stmt->implements as $iface) {
                        $ifaceFqcn = ltrim($iface->toString(), '\\');
                        if (isset($targetSet[$ifaceFqcn])) {
                            $implements[$ifaceFqcn][] = $fqcn;
                        }
                    }
                }
            }

            // Check trait uses
            if (isset($enabledTypes[self::TYPE_TRAITS])) {
                foreach ($stmt->stmts as $member) {
                    if ($member instanceof Stmt\TraitUse) {
                        foreach ($member->traits as $trait) {
                            $traitFqcn = ltrim($trait->toString(), '\\');
                            if (isset($targetSet[$traitFqcn])) {
                                $traits[$traitFqcn][] = $fqcn;
                            }
                        }
                    }
                }
            }

            // Check general usage (type hints, new, instanceof, static calls, etc.)
            // Walks the class body excluding trait-use declarations (already covered above).
            if (isset($enabledTypes[self::TYPE_USES])) {
                $usedTargets = [];
                foreach ($stmt->stmts as $member) {
                    if ($member instanceof Stmt\TraitUse) {
                        continue;
                    }
                    $this->findNameReferences($member, $targetSet, $usedTargets);
                }
                foreach (array_keys($usedTargets) as $target) {
                    $uses[$target][] = $fqcn;
                }
            }
        }
    }

    /**
     * Recursively walk an AST node collecting all Name references that match targets.
     *
     * @param array<string, bool> $targetSet
     * @param array<string, bool> $found     collected target FQCNs (used as set)
     */
    private function findNameReferences(Node $node, array $targetSet, array &$found): void
    {
        if ($node instanceof Node\Name) {
            $name = ltrim($node->toString(), '\\');
            if (isset($targetSet[$name])) {
                $found[$name] = true;
            }
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName; // @phpstan-ignore property.dynamicName
            if ($subNode instanceof Node) {
                $this->findNameReferences($subNode, $targetSet, $found);
            } elseif (is_array($subNode)) {
                foreach ($subNode as $item) {
                    if ($item instanceof Node) {
                        $this->findNameReferences($item, $targetSet, $found);
                    }
                }
            }
        }
    }

    /**
     * Sort: keys alphabetically, values within each key alphabetically.
     *
     * @param array<string, string[]> $grouped
     * @return array<string, string[]>
     */
    private function sortGrouped(array $grouped): array
    {
        foreach ($grouped as &$values) {
            sort($values);
        }
        unset($values);
        ksort($grouped);

        return $grouped;
    }

    private function resolveFqcn(Stmt\ClassLike $node): string
    {
        if ($node->namespacedName !== null) {
            return $node->namespacedName->toString();
        }

        return $node->name !== null ? $node->name->toString() : '__anonymous__';
    }
}
