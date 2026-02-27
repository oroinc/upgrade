<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Analysis;

use Oro\UpgradeToolkit\Semadiff\Extractor\ClassInfoExtractor;
use Oro\UpgradeToolkit\Semadiff\Extractor\MethodInfo;
use Oro\UpgradeToolkit\Semadiff\FqcnPathMap;
use Oro\UpgradeToolkit\Semadiff\Git\DivergenceAnalyzer;
use Oro\UpgradeToolkit\Semadiff\Resolver\UsageInfo;

final class ResolutionChecker
{
    private ClassInfoExtractor $extractor;

    /** @var array<string, ?string> depFqcn => source code (avoids re-reads) */
    private array $codeByFqcn = [];

    /** @var array<string, \PhpParser\Node\Stmt[]|null> depFqcn => parsed AST (avoids re-parses) */
    private array $astByFqcn = [];

    public function __construct()
    {
        $this->extractor = new ClassInfoExtractor();
    }

    /**
     * Check all BC items for resolution status.
     *
     * @param array<string, array{details: string[], path: string, changedMethods: string[], constructorChanged: bool}> $signatureInfo
     * @param array<string, UsageInfo[]> $bcUsageMap
     * @param array<string, string[]> $removedMembers    vendorFqcn → removed detail strings
     * @param array<string, string[]> $removedMemberDeps vendorFqcn → dependent FQCNs
     * @param string[] $deletedFqcns
     * @param array<string, string[]> $deletedRefs       deletedFqcn → dependent FQCNs
     * @param string $afterDir
     * @param string[] $projectDirs
     * @param string[] $warnings Collected warnings (output parameter)
     * @return array{vendorItems: array<string, array{details: string[], items: list<array<string, mixed>>}>, deletedItems: array<string, array{refs: list<array{depFqcn: string, resolved: bool}>}>, totalItems: int, resolvedItems: int}
     */
    public function checkAll(
        array $signatureInfo,
        array $bcUsageMap,
        array $removedMembers,
        array $removedMemberDeps,
        array $deletedFqcns,
        array $deletedRefs,
        string $afterDir,
        array $projectDirs,
        array &$warnings = [],
        ?FqcnPathMap $afterPaths = null,
        ?FqcnPathMap $projectPaths = null,
    ): array {
        $this->codeByFqcn = [];
        $this->astByFqcn = [];

        $vendorItems = [];
        $deletedItems = [];
        $totalItems = 0;
        $resolvedItems = 0;

        $analyzer = new DivergenceAnalyzer();

        // ── Signature changes ────────────────────────────────────────
        foreach ($signatureInfo as $vendorFqcn => $info) {
            $usages = $bcUsageMap[$vendorFqcn] ?? [];
            if ($usages === []) {
                continue;
            }

            $items = [];

            // Get vendor's new method signatures
            $vendorMethods = $this->extractVendorMethods($analyzer, $afterDir, $vendorFqcn, $warnings, $afterPaths);
            foreach ($usages as $usage) {
                $depCode = $this->readDependentCode($analyzer, $projectDirs, $projectPaths, $usage->dependentFqcn);
                $depMethods = $depCode !== null ? $this->extractMethodMap($depCode) : [];

                // Method overrides
                foreach ($usage->overriddenMethods as $method) {
                    $item = $this->checkMethodOverride($vendorMethods, $depMethods, $usage->dependentFqcn, $method);
                    $items[] = $item;
                    $totalItems++;
                    if ((bool) $item['resolved']) {
                        $resolvedItems++;
                    }
                }

                // Interface implementations
                foreach ($usage->implementedMethods as $method) {
                    $item = $this->checkInterfaceImpl($vendorMethods, $depMethods, $usage->dependentFqcn, $method);
                    $items[] = $item;
                    $totalItems++;
                    if ((bool) $item['resolved']) {
                        $resolvedItems++;
                    }
                }

                // Parent calls
                foreach ($usage->parentMethodCalls as $method) {
                    $item = $this->checkCallArgs($vendorMethods, $depCode ?? '', $usage->dependentFqcn, $method, 'parent_call');
                    $items[] = $item;
                    $totalItems++;
                    if ((bool) $item['resolved']) {
                        $resolvedItems++;
                    }
                }

                // Instance calls
                foreach ($usage->instanceMethodCalls as $method) {
                    $item = $this->checkCallArgs($vendorMethods, $depCode ?? '', $usage->dependentFqcn, $method, 'instance_call');
                    $items[] = $item;
                    $totalItems++;
                    if ((bool) $item['resolved']) {
                        $resolvedItems++;
                    }
                }

                // Static calls
                foreach ($usage->staticMethodCalls as $method) {
                    $item = $this->checkCallArgs($vendorMethods, $depCode ?? '', $usage->dependentFqcn, $method, 'static_call');
                    $items[] = $item;
                    $totalItems++;
                    if ((bool) $item['resolved']) {
                        $resolvedItems++;
                    }
                }

                // Constructor call (new Vendor())
                if ($usage->callsConstructor && !$usage->overridesConstructor) {
                    $item = $this->checkCallArgs($vendorMethods, $depCode ?? '', $usage->dependentFqcn, '__construct', 'instance_call', $vendorFqcn);
                    $items[] = $item;
                    $totalItems++;
                    if ((bool) $item['resolved']) {
                        $resolvedItems++;
                    }
                }
            }

            if ($items !== []) {
                $vendorItems[$vendorFqcn] = [
                    'details' => $info['details'],
                    'items' => $items,
                ];
            }
        }

        // ── Removed members ──────────────────────────────────────────
        foreach ($removedMembers as $vendorFqcn => $removedDetails) {
            $deps = $removedMemberDeps[$vendorFqcn] ?? [];
            $items = $vendorItems[$vendorFqcn]['items'] ?? [];

            foreach ($removedDetails as $detail) {
                $memberName = $this->extractMemberNameFromDetail($detail);
                if ($memberName === null) {
                    continue;
                }

                foreach ($deps as $depFqcn) {
                    $depCode = $this->readDependentCode($analyzer, $projectDirs, $projectPaths, $depFqcn);
                    $referenced = $depCode === null || $this->memberReferencedInCode($depCode, $memberName);

                    if (!$referenced) {
                        continue; // Member was never used by this dependent
                    }

                    $items[] = [
                        'type' => 'removed_member',
                        'depFqcn' => $depFqcn,
                        'method' => $memberName,
                        'resolved' => false,
                        'note' => "Still references `$memberName`",
                        'paramDiff' => '',
                    ];
                    $totalItems++;
                }
            }

            if ($items !== []) {
                $vendorItems[$vendorFqcn] = [
                    'details' => $vendorItems[$vendorFqcn]['details'] ?? $removedDetails,
                    'items' => $items,
                ];
            }
        }

        // ── Deleted classes ──────────────────────────────────────────
        foreach ($deletedFqcns as $deletedFqcn) {
            $refs = $deletedRefs[$deletedFqcn] ?? [];
            if ($refs === []) {
                continue;
            }

            $shortName = $this->shortName($deletedFqcn);
            $refItems = [];

            foreach ($refs as $depFqcn) {
                $depCode = $this->readDependentCode($analyzer, $projectDirs, $projectPaths, $depFqcn);
                $resolved = $depCode !== null && !$this->memberReferencedInCode($depCode, $shortName);

                $refItems[] = [
                    'depFqcn' => $depFqcn,
                    'resolved' => $resolved,
                ];
                $totalItems++;
                if ($resolved) {
                    $resolvedItems++;
                }
            }

            $deletedItems[$deletedFqcn] = ['refs' => $refItems];
        }

        return [
            'vendorItems' => $vendorItems,
            'deletedItems' => $deletedItems,
            'totalItems' => $totalItems,
            'resolvedItems' => $resolvedItems,
        ];
    }

    /**
     * Check if a method override matches the vendor signature.
     *
     * @param array<string, MethodInfo> $vendorMethods
     * @param array<string, MethodInfo> $depMethods
     * @return array<string, mixed>
     */
    private function checkMethodOverride(
        array $vendorMethods,
        array $depMethods,
        string $depFqcn,
        string $method,
    ): array {
        $vendorMethod = $vendorMethods[$method] ?? null;
        $depMethod = $depMethods[$method] ?? null;

        if ($vendorMethod === null || $depMethod === null) {
            return [
                'type' => 'method_override',
                'depFqcn' => $depFqcn,
                'method' => $method,
                'resolved' => false,
                'note' => $depMethod === null ? "Method `$method` not found in dependent" : "Vendor method `$method` not found",
                'paramDiff' => '',
            ];
        }

        $compatible = $this->paramsCompatible($vendorMethod, $depMethod);

        return [
            'type' => 'method_override',
            'depFqcn' => $depFqcn,
            'method' => $method,
            'resolved' => $compatible,
            'note' => $compatible ? 'Signature matches' : 'Signature mismatch',
            'paramDiff' => $compatible ? '' : $this->buildParamDiff($vendorMethod, $depMethod),
        ];
    }

    /**
     * Check interface implementation matches vendor signature.
     *
     * @param array<string, MethodInfo> $vendorMethods
     * @param array<string, MethodInfo> $depMethods
     * @return array<string, mixed>
     */
    private function checkInterfaceImpl(
        array $vendorMethods,
        array $depMethods,
        string $depFqcn,
        string $method,
    ): array {
        $result = $this->checkMethodOverride($vendorMethods, $depMethods, $depFqcn, $method);
        $result['type'] = 'interface_impl';

        return $result;
    }

    /**
     * Check if call arguments match vendor required params.
     *
     * @param array<string, MethodInfo> $vendorMethods
     * @return array<string, mixed>
     */
    private function checkCallArgs(
        array $vendorMethods,
        string $depCode,
        string $depFqcn,
        string $method,
        string $type,
        ?string $vendorFqcn = null,
    ): array {
        $vendorMethod = $vendorMethods[$method] ?? null;
        if ($vendorMethod === null) {
            return [
                'type' => $type,
                'depFqcn' => $depFqcn,
                'method' => $method,
                'resolved' => false,
                'note' => "Vendor method `$method` not found",
                'paramDiff' => '',
            ];
        }

        $className = $vendorFqcn !== null ? $this->shortName($vendorFqcn) : null;
        $argInfo = $this->findCallArgInfo($depCode, $method, $type, $className, $depFqcn);

        if ($argInfo === null) {
            return [
                'type' => $type,
                'depFqcn' => $depFqcn,
                'method' => $method,
                'resolved' => false,
                'note' => "Calls `$method` (check arg count manually)",
                'paramDiff' => '',
            ];
        }

        if ($argInfo['namedArgs'] !== []) {
            return $this->checkNamedArgs($vendorMethod, $depFqcn, $method, $type, $argInfo['namedArgs']);
        }

        $requiredCount = $this->countRequiredParams($vendorMethod);
        $resolved = $argInfo['count'] >= $requiredCount;

        return [
            'type' => $type,
            'depFqcn' => $depFqcn,
            'method' => $method,
            'resolved' => $resolved,
            'note' => $resolved
                ? "Arg count OK ({$argInfo['count']} args)"
                : "Has {$argInfo['count']} arg(s), expected at least $requiredCount",
            'paramDiff' => '',
        ];
    }

    /**
     * Check if named arguments are compatible with the vendor method signature.
     *
     * @param string[] $calledNames
     * @return array<string, mixed>
     */
    public function checkNamedArgs(
        MethodInfo $vendorMethod,
        string $depFqcn,
        string $method,
        string $type,
        array $calledNames,
    ): array {
        $validNames = [];
        foreach ($vendorMethod->params as $param) {
            $name = $param['name'] ?? '';
            assert(is_string($name));
            $validNames[$name] = true;
        }

        $unknowns = [];
        foreach ($calledNames as $name) {
            if (!isset($validNames[$name])) {
                $unknowns[] = $name;
            }
        }

        $calledSet = array_flip($calledNames);
        $missing = [];
        foreach ($vendorMethod->params as $param) {
            $hasDefault = (bool) ($param['hasDefault'] ?? false);
            $isVariadic = (bool) ($param['variadic'] ?? false);
            if ($hasDefault || $isVariadic) {
                continue;
            }

            $name = $param['name'] ?? '';
            assert(is_string($name));
            if (!isset($calledSet[$name])) {
                $missing[] = $name;
            }
        }

        $resolved = $unknowns === [] && $missing === [];

        if ($resolved) {
            $note = sprintf('Named args OK (%d args)', count($calledNames));
        } else {
            $parts = [];
            if ($unknowns !== []) {
                $parts[] = 'unknown param(s): ' . implode(', ', $unknowns);
            }
            if ($missing !== []) {
                $parts[] = 'missing required: ' . implode(', ', $missing);
            }
            $note = implode('; ', $parts);
        }

        return [
            'type' => $type,
            'depFqcn' => $depFqcn,
            'method' => $method,
            'resolved' => $resolved,
            'note' => $note,
            'paramDiff' => '',
        ];
    }

    /**
     * Check if two method signatures are compatible (required params count + types match).
     */
    public function paramsCompatible(MethodInfo $vendor, MethodInfo $project): bool
    {
        $vendorRequired = $this->countRequiredParams($vendor);
        $projectRequired = $this->countRequiredParams($project);

        if ($vendorRequired !== $projectRequired) {
            return false;
        }

        // Compare types for overlapping positions
        $maxCheck = min(count($vendor->params), count($project->params));
        for ($i = 0; $i < $maxCheck; $i++) {
            $vType = $this->normalizeParamType($vendor->params[$i]);
            $pType = $this->normalizeParamType($project->params[$i]);

            if ($vType !== null && $pType !== null && $vType !== $pType) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build a diff-style representation of parameter changes.
     */
    public function buildParamDiff(MethodInfo $vendor, MethodInfo $project): string
    {
        $lines = [];
        $methodName = $vendor->name;
        $lines[] = " $methodName(";

        $maxParams = max(count($vendor->params), count($project->params));

        for ($i = 0; $i < $maxParams; $i++) {
            $vParam = $vendor->params[$i] ?? null;
            $pParam = $project->params[$i] ?? null;

            $vStr = $vParam !== null ? $this->paramToString($vParam) : null;
            $pStr = $pParam !== null ? $this->paramToString($pParam) : null;

            if ($vStr === $pStr) {
                $lines[] = "     $vStr,";
            } else {
                if ($pStr !== null) {
                    $lines[] = "-    $pStr,";
                }
                if ($vStr !== null) {
                    $lines[] = "+    $vStr,";
                }
            }
        }

        $lines[] = ' )';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $param
     */
    private function paramToString(array $param): string
    {
        $parts = [];
        $type = isset($param['type']) && is_string($param['type']) ? $param['type'] : null;
        if ($type !== null) {
            $parts[] = $type;
        }

        $name = isset($param['name']) && is_string($param['name']) ? $param['name'] : '?';

        if (isset($param['variadic']) && $param['variadic'] === true) {
            $parts[] = '...$' . $name;
        } else {
            $parts[] = '$' . $name;
        }

        return implode(' ', $parts);
    }

    /**
     * @param string[] $warnings
     * @return array<string, MethodInfo>
     */
    private function extractVendorMethods(DivergenceAnalyzer $analyzer, string $afterDir, string $vendorFqcn, array &$warnings, ?FqcnPathMap $afterPaths = null): array
    {
        $file = $analyzer->findFileForFqcn($afterDir, $vendorFqcn, $afterPaths);
        if ($file === null) {
            $warnings[] = sprintf('Could not locate file for vendor FQCN: %s', $vendorFqcn);

            return [];
        }

        $code = file_get_contents($file);
        if ($code === false) {
            $warnings[] = sprintf('Could not read vendor file for: %s', $vendorFqcn);

            return [];
        }

        return $this->extractMethodMap($code, $warnings);
    }

    /**
     * @param string[] $warnings
     * @return array<string, MethodInfo>
     */
    private function extractMethodMap(string $code, array &$warnings = []): array
    {
        try {
            $classes = $this->extractor->extract($code);
        } catch (\Throwable $e) {
            $warnings[] = sprintf('Failed to parse code: %s', $e->getMessage());

            return [];
        }

        $methods = [];
        foreach ($classes as $class) {
            foreach ($class->methods as $method) {
                $methods[$method->name] = $method;
            }
        }

        return $methods;
    }

    /**
     * @param string[] $projectDirs
     */
    private function readDependentCode(DivergenceAnalyzer $analyzer, array $projectDirs, ?FqcnPathMap $projectPaths, string $depFqcn): ?string
    {
        if (array_key_exists($depFqcn, $this->codeByFqcn)) {
            return $this->codeByFqcn[$depFqcn];
        }

        $code = null;

        // Try path map first (O(1) lookup)
        $mapped = $projectPaths?->get($depFqcn);
        if ($mapped !== null) {
            $result = file_get_contents($mapped);
            if ($result !== false) {
                $code = $result;
            }
        }

        if ($code === null) {
            foreach ($projectDirs as $dir) {
                $file = $analyzer->findFileForFqcn($dir, $depFqcn);
                if ($file !== null) {
                    $result = file_get_contents($file);
                    if ($result !== false) {
                        $code = $result;
                        break;
                    }
                }
            }
        }

        $this->codeByFqcn[$depFqcn] = $code;

        return $code;
    }

    private function countRequiredParams(MethodInfo $method): int
    {
        $count = 0;
        foreach ($method->params as $param) {
            $hasDefault = (bool) ($param['hasDefault'] ?? false);
            $isVariadic = (bool) ($param['variadic'] ?? false);
            if (!$hasDefault && !$isVariadic) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $param
     */
    private function normalizeParamType(array $param): ?string
    {
        $type = isset($param['type']) && is_string($param['type']) ? $param['type'] : null;
        if ($type === null) {
            return null;
        }

        $type = ltrim($type, '\\');

        if (str_contains($type, '|')) {
            $parts = explode('|', $type);
            sort($parts);

            return implode('|', $parts);
        }

        if (str_contains($type, '&')) {
            $parts = explode('&', $type);
            sort($parts);

            return implode('&', $parts);
        }

        return $type;
    }

    /**
     * Try to find argument info for a specific call in the code.
     * Returns null if the call pattern can't be determined.
     *
     * @return array{count: int, namedArgs: string[]}|null
     */
    private function findCallArgInfo(string $code, string $method, string $type, ?string $className = null, ?string $depFqcn = null): ?array
    {
        $cacheKey = $depFqcn ?? $code;

        if (array_key_exists($cacheKey, $this->astByFqcn)) {
            $ast = $this->astByFqcn[$cacheKey];
        } else {
            $ast = $this->extractor->parse($code);
            $this->astByFqcn[$cacheKey] = $ast;
        }

        if ($ast === null) {
            return null;
        }

        return $this->findArgInfoInNodes($ast, $method, $type, $className);
    }

    /**
     * @param \PhpParser\Node[] $nodes
     * @return array{count: int, namedArgs: string[]}|null
     */
    private function findArgInfoInNodes(array $nodes, string $method, string $type, ?string $className = null): ?array
    {
        foreach ($nodes as $node) {
            $result = $this->findArgInfoInNode($node, $method, $type, $className);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @return array{count: int, namedArgs: string[]}|null
     */
    private function findArgInfoInNode(\PhpParser\Node $node, string $method, string $type, ?string $className = null): ?array
    {
        // parent::method() calls
        if (
            $type === 'parent_call'
            && $node instanceof \PhpParser\Node\Expr\StaticCall
            && $node->class instanceof \PhpParser\Node\Name
            && $node->class->toString() === 'parent'
            && $node->name instanceof \PhpParser\Node\Identifier
            && $node->name->toString() === $method
        ) {
            return $this->extractArgInfo($node->args);
        }

        // Static calls
        if (
            $type === 'static_call'
            && $node instanceof \PhpParser\Node\Expr\StaticCall
            && $node->name instanceof \PhpParser\Node\Identifier
            && $node->name->toString() === $method
        ) {
            return $this->extractArgInfo($node->args);
        }

        // Instance calls
        if (
            $type === 'instance_call'
            && $node instanceof \PhpParser\Node\Expr\MethodCall
            && $node->name instanceof \PhpParser\Node\Identifier
            && $node->name->toString() === $method
        ) {
            return $this->extractArgInfo($node->args);
        }

        // new Vendor() for constructor
        if (
            $type === 'instance_call'
            && $method === '__construct'
            && $node instanceof \PhpParser\Node\Expr\New_
            && $this->newNodeMatchesClass($node, $className)
        ) {
            return $this->extractArgInfo($node->args);
        }

        // Recurse
        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName; // @phpstan-ignore property.dynamicName
            if ($subNode instanceof \PhpParser\Node) {
                $result = $this->findArgInfoInNode($subNode, $method, $type, $className);
                if ($result !== null) {
                    return $result;
                }
            } elseif (is_array($subNode)) {
                foreach ($subNode as $item) {
                    if ($item instanceof \PhpParser\Node) {
                        $result = $this->findArgInfoInNode($item, $method, $type, $className);
                        if ($result !== null) {
                            return $result;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if a New_ node matches the expected vendor class name.
     * If no class name is specified, any New_ node matches (backwards-compatible).
     */
    private function newNodeMatchesClass(\PhpParser\Node\Expr\New_ $node, ?string $className): bool
    {
        if ($className === null) {
            return true;
        }

        if (!$node->class instanceof \PhpParser\Node\Name) {
            return false;
        }

        // Compare short name — the use-statement resolves the FQCN to a short name in most code
        $nodeName = $node->class->getLast();

        return $nodeName === $className;
    }

    /**
     * @param array<\PhpParser\Node\Arg|\PhpParser\Node\VariadicPlaceholder> $args
     * @return array{count: int, namedArgs: string[]}
     */
    private function extractArgInfo(array $args): array
    {
        $namedArgs = [];
        foreach ($args as $arg) {
            if ($arg instanceof \PhpParser\Node\Arg && $arg->name !== null) {
                $namedArgs[] = $arg->name->toString();
            }
        }

        return [
            'count' => count($args),
            'namedArgs' => $namedArgs,
        ];
    }

    public function memberReferencedInCode(string $code, string $memberName): bool
    {
        $escaped = preg_quote($memberName, '/');

        // Properties start with $ (non-word char) — only need trailing boundary
        // Methods/constants need leading boundary too to avoid partial matches
        if (str_starts_with($memberName, '$')) {
            return preg_match('/' . $escaped . '\b/', $code) === 1;
        }

        return preg_match('/\b' . $escaped . '\b/', $code) === 1;
    }

    private function extractMemberNameFromDetail(string $detail): ?string
    {
        // "Method removed: Foo::bar" → "bar"
        // "Property removed: Foo::$baz" → "$baz"
        // "Constant removed: Foo::QUX" → "QUX"
        $colonPos = strpos($detail, '::');
        if ($colonPos === false) {
            return null;
        }

        return substr($detail, $colonPos + 2);
    }

    private function shortName(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos !== false ? substr($fqcn, $pos + 1) : $fqcn;
    }
}
