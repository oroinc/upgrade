<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Resolver;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

final class UsageAnalyzer
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
    }

    /**
     * Analyze how a dependent class uses a vendor class at the method level.
     *
     * @param string   $vendorFqcn        The vendor FQCN being analyzed
     * @param string[] $changedMethods     Method names that changed in vendor
     * @param bool     $constructorChanged Whether the vendor constructor changed
     * @param string   $dependentFqcn      The dependent class FQCN
     * @param string   $dependentCode      Source code of the dependent class
     * @param string   $relationType       Relationship type (extends, implements, traits, uses)
     * @param string[] $warnings           Collected parse warnings (output parameter)
     */
    public function analyze(
        string $vendorFqcn,
        array $changedMethods,
        bool $constructorChanged,
        string $dependentFqcn,
        string $dependentCode,
        string $relationType,
        array &$warnings = [],
    ): UsageInfo {
        try {
            $ast = $this->parser->parse($dependentCode);
        } catch (\Throwable $e) {
            $warnings[] = sprintf('Failed to parse %s: %s', $dependentFqcn, $e->getMessage());

            return $this->emptyUsageInfo($dependentFqcn);
        }
        if ($ast === null) {
            $warnings[] = sprintf('Failed to parse %s: parser returned null', $dependentFqcn);

            return $this->emptyUsageInfo($dependentFqcn);
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $ast = $traverser->traverse($ast);

        $classNode = $this->findClassNode($ast, $dependentFqcn);
        if ($classNode === null) {
            return $this->emptyUsageInfo($dependentFqcn);
        }

        $changedSet = array_flip($changedMethods);
        $vendorShortName = $this->shortName($vendorFqcn);
        $normalizedVendor = ltrim($vendorFqcn, '\\');

        // 1. Method overrides
        $overriddenMethods = [];
        $overridesConstructor = false;
        foreach ($classNode->stmts as $member) {
            if (!$member instanceof Stmt\ClassMethod) {
                continue;
            }
            $name = $member->name->toString();
            if ($name === '__construct' && $constructorChanged) {
                $overridesConstructor = true;
            }
            if (isset($changedSet[$name]) && $name !== '__construct') {
                $overriddenMethods[] = $name;
            }
        }

        // 2. Parent calls and constructor calls
        $parentMethodCalls = [];
        $callsConstructor = false;
        $instanceMethodCalls = [];
        $staticMethodCalls = [];

        $this->walkStmts(
            $classNode->stmts,
            $changedSet,
            $constructorChanged,
            $normalizedVendor,
            $vendorShortName,
            $parentMethodCalls,
            $callsConstructor,
            $instanceMethodCalls,
            $staticMethodCalls,
        );

        // 3. Interface implementation
        $implementsInterface = false;
        $implementedMethods = [];
        if ($relationType === DependencyResolver::TYPE_IMPLEMENTS) {
            $implementsInterface = true;
            foreach ($classNode->stmts as $member) {
                if ($member instanceof Stmt\ClassMethod && isset($changedSet[$member->name->toString()])) {
                    $implementedMethods[] = $member->name->toString();
                }
            }
        }

        // 4. Trait usage
        $usesTrait = $relationType === DependencyResolver::TYPE_TRAITS;

        return new UsageInfo(
            dependentFqcn: $dependentFqcn,
            overridesConstructor: $overridesConstructor,
            callsConstructor: $callsConstructor,
            overriddenMethods: array_values(array_unique($overriddenMethods)),
            parentMethodCalls: array_values(array_unique($parentMethodCalls)),
            instanceMethodCalls: array_values(array_unique($instanceMethodCalls)),
            staticMethodCalls: array_values(array_unique($staticMethodCalls)),
            implementsInterface: $implementsInterface,
            implementedMethods: array_values(array_unique($implementedMethods)),
            usesTrait: $usesTrait,
        );
    }

    /**
     * @param Stmt[]          $stmts
     * @param array<string, int> $changedSet
     * @param string[]        $parentMethodCalls
     * @param string[]        $instanceMethodCalls
     * @param string[]        $staticMethodCalls
     */
    private function walkStmts(
        array $stmts,
        array $changedSet,
        bool $constructorChanged,
        string $normalizedVendor,
        string $vendorShortName,
        array &$parentMethodCalls,
        bool &$callsConstructor,
        array &$instanceMethodCalls,
        array &$staticMethodCalls,
    ): void {
        foreach ($stmts as $stmt) {
            $this->walkNode(
                $stmt,
                $changedSet,
                $constructorChanged,
                $normalizedVendor,
                $vendorShortName,
                $parentMethodCalls,
                $callsConstructor,
                $instanceMethodCalls,
                $staticMethodCalls,
            );
        }
    }

    /**
     * @param array<string, int> $changedSet
     * @param string[]        $parentMethodCalls
     * @param string[]        $instanceMethodCalls
     * @param string[]        $staticMethodCalls
     */
    private function walkNode(
        Node $node,
        array $changedSet,
        bool $constructorChanged,
        string $normalizedVendor,
        string $vendorShortName,
        array &$parentMethodCalls,
        bool &$callsConstructor,
        array &$instanceMethodCalls,
        array &$staticMethodCalls,
    ): void {
        // parent::method() calls
        if (
            $node instanceof Expr\StaticCall
            && $node->class instanceof Name
            && $node->class->toString() === 'parent'
            && $node->name instanceof Node\Identifier
        ) {
            $methodName = $node->name->toString();
            if ($methodName === '__construct' && $constructorChanged) {
                $callsConstructor = true;
            }
            if (isset($changedSet[$methodName])) {
                $parentMethodCalls[] = $methodName;
            }
        }

        // new VendorFqcn() calls
        if (
            $node instanceof Expr\New_
            && $node->class instanceof Name
            && $constructorChanged
        ) {
            $className = ltrim($node->class->toString(), '\\');
            if ($className === $normalizedVendor) {
                $callsConstructor = true;
            }
        }

        // VendorFqcn::method() static calls
        if (
            $node instanceof Expr\StaticCall
            && $node->class instanceof Name
            && $node->name instanceof Node\Identifier
        ) {
            $className = ltrim($node->class->toString(), '\\');
            if ($className === $normalizedVendor && $className !== 'parent') {
                $methodName = $node->name->toString();
                if (isset($changedSet[$methodName])) {
                    $staticMethodCalls[] = $methodName;
                }
            }
        }

        // $var->method() instance calls — match by vendor short name in variable type hints
        if (
            $node instanceof Expr\MethodCall
            && $node->name instanceof Node\Identifier
        ) {
            $methodName = $node->name->toString();
            if (isset($changedSet[$methodName])) {
                $instanceMethodCalls[] = $methodName;
            }
        }

        // Recurse into sub-nodes
        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName; // @phpstan-ignore property.dynamicName
            if ($subNode instanceof Node) {
                $this->walkNode(
                    $subNode,
                    $changedSet,
                    $constructorChanged,
                    $normalizedVendor,
                    $vendorShortName,
                    $parentMethodCalls,
                    $callsConstructor,
                    $instanceMethodCalls,
                    $staticMethodCalls,
                );
            } elseif (is_array($subNode)) {
                foreach ($subNode as $item) {
                    if ($item instanceof Node) {
                        $this->walkNode(
                            $item,
                            $changedSet,
                            $constructorChanged,
                            $normalizedVendor,
                            $vendorShortName,
                            $parentMethodCalls,
                            $callsConstructor,
                            $instanceMethodCalls,
                            $staticMethodCalls,
                        );
                    }
                }
            }
        }
    }

    /**
     * @param Node[] $stmts
     */
    private function findClassNode(array $stmts, string $targetFqcn): ?Stmt\ClassLike
    {
        $normalizedTarget = ltrim($targetFqcn, '\\');

        foreach ($stmts as $stmt) {
            if ($stmt instanceof Stmt\Namespace_ && $stmt->stmts !== null) {
                $found = $this->findClassNode($stmt->stmts, $targetFqcn);
                if ($found !== null) {
                    return $found;
                }
                continue;
            }

            if ($stmt instanceof Stmt\ClassLike && $stmt->namespacedName !== null) {
                if ($stmt->namespacedName->toString() === $normalizedTarget) {
                    return $stmt;
                }
            }
        }

        return null;
    }

    private function shortName(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos !== false ? substr($fqcn, $pos + 1) : $fqcn;
    }

    private function emptyUsageInfo(string $dependentFqcn): UsageInfo
    {
        return new UsageInfo(
            dependentFqcn: $dependentFqcn,
            overridesConstructor: false,
            callsConstructor: false,
            overriddenMethods: [],
            parentMethodCalls: [],
            instanceMethodCalls: [],
            staticMethodCalls: [],
            implementsInterface: false,
            implementedMethods: [],
            usesTrait: false,
        );
    }
}
