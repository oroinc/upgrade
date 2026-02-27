<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Extractor;

use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\UnionType;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Oro\UpgradeToolkit\Semadiff\Normalizer\BodyNormalizer;
use Oro\UpgradeToolkit\Semadiff\Normalizer\SemanticHasher;
use RuntimeException;

final class ClassInfoExtractor
{
    private Parser $parser;
    private SemanticHasher $hasher;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->hasher = new SemanticHasher();
    }

    /**
     * @return ClassInfo[] Returns one ClassInfo per class/interface/trait/enum in the file,
     *                     plus a "virtual" entry for top-level functions
     */
    public function extract(string $code): array
    {
        return $this->extractWithErrors($code)[0];
    }

    /**
     * Parse and extract, returning both results and any parse errors/warnings.
     *
     * @return array{0: ClassInfo[], 1: string[]} [classInfos, parseErrors]
     */
    public function extractWithErrors(string $code): array
    {
        $errorHandler = new ErrorHandler\Collecting();
        $ast = $this->parser->parse($code, $errorHandler);

        $parseErrors = [];
        foreach ($errorHandler->getErrors() as $error) {
            $parseErrors[] = $error->getMessage();
        }

        if ($ast === null) {
            throw new RuntimeException('Failed to parse PHP code' . ($parseErrors !== [] ? ': ' . implode('; ', $parseErrors) : ''));
        }

        // Normalize the AST (strip comments, #[\Override], etc.)
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new BodyNormalizer());
        $ast = $traverser->traverse($ast);

        $results = [];

        // Find all class-like declarations
        $classNodes = $this->findNodes($ast, [
            Stmt\Class_::class,
            Stmt\Interface_::class,
            Stmt\Trait_::class,
            Stmt\Enum_::class,
        ]);

        foreach ($classNodes as $classNode) {
            $results[] = $this->extractClassInfo($classNode);
        }

        // Find top-level functions
        $functions = $this->findTopLevelFunctions($ast);
        if ($functions !== []) {
            $methods = [];
            foreach ($functions as $func) {
                $methods[] = $this->extractFunctionInfo($func);
            }

            $results[] = new ClassInfo(
                name: '__TOP_LEVEL_FUNCTIONS__',
                type: 'functions',
                isFinal: false,
                extends: null,
                implements: [],
                traits: [],
                methods: $methods,
                properties: [],
                constants: [],
            );
        }

        return [$results, $parseErrors];
    }

    /**
     * Extract FQCNs for all class-like declarations (class, interface, trait, enum) in the code.
     *
     * @return string[] e.g. ['Oro\Bundle\OrderBundle\Migrations\Schema\v1_17\AddOrderLineItemChecksumColumn']
     */
    public function extractFqcns(string $code): array
    {
        $ast = $this->parser->parse($code);
        if ($ast === null) {
            return [];
        }

        $fqcns = [];
        $this->collectFqcns($ast, '', $fqcns);

        return $fqcns;
    }

    /**
     * @param Node\Stmt[] $stmts
     * @param string[] $fqcns collected results
     */
    private function collectFqcns(array $stmts, string $namespace, array &$fqcns): void
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Stmt\Namespace_) {
                $ns = $stmt->name !== null ? $stmt->name->toString() : '';
                if ($stmt->stmts !== null) {
                    $this->collectFqcns($stmt->stmts, $ns, $fqcns);
                }
                continue;
            }

            if (
                ($stmt instanceof Stmt\Class_
                || $stmt instanceof Stmt\Interface_
                || $stmt instanceof Stmt\Trait_
                || $stmt instanceof Stmt\Enum_)
                && $stmt->name !== null
            ) {
                $name = $stmt->name->toString();
                $fqcns[] = $namespace !== '' ? $namespace . '\\' . $name : $name;
            }
        }
    }

    /**
     * Extract FQCNs from a pre-parsed AST (avoids re-parsing).
     *
     * @param Node\Stmt[] $ast
     * @return string[]
     */
    public function extractFqcnsFromAst(array $ast): array
    {
        $fqcns = [];
        $this->collectFqcns($ast, '', $fqcns);

        return $fqcns;
    }

    /**
     * Extract ClassInfo from a pre-parsed AST (avoids re-parsing).
     * The AST should be the raw parser output — BodyNormalizer is applied internally.
     *
     * @param Node\Stmt[] $ast
     * @return array{0: ClassInfo[], 1: string[]} [classInfos, parseErrors]
     */
    public function extractWithErrorsFromAst(array $ast): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new BodyNormalizer());
        $normalized = $traverser->traverse($ast);

        $results = [];
        $classNodes = $this->findNodes($normalized, [
            Stmt\Class_::class,
            Stmt\Interface_::class,
            Stmt\Trait_::class,
            Stmt\Enum_::class,
        ]);

        foreach ($classNodes as $classNode) {
            $results[] = $this->extractClassInfo($classNode);
        }

        $functions = $this->findTopLevelFunctions($normalized);
        if ($functions !== []) {
            $methods = [];
            foreach ($functions as $func) {
                $methods[] = $this->extractFunctionInfo($func);
            }

            $results[] = new ClassInfo(
                name: '__TOP_LEVEL_FUNCTIONS__',
                type: 'functions',
                isFinal: false,
                extends: null,
                implements: [],
                traits: [],
                methods: $methods,
                properties: [],
                constants: [],
            );
        }

        return [$results, []];
    }

    /**
     * Parse the given code and return the AST, or null on failure.
     *
     * @return Node\Stmt[]|null
     */
    public function parse(string $code): ?array
    {
        return $this->parser->parse($code);
    }

    private function extractClassInfo(Stmt\ClassLike $node): ClassInfo
    {
        $name = $node->name !== null ? $node->name->toString() : '__anonymous__';
        $type = match (true) {
            $node instanceof Stmt\Class_ => 'class',
            $node instanceof Stmt\Interface_ => 'interface',
            $node instanceof Stmt\Trait_ => 'trait',
            $node instanceof Stmt\Enum_ => 'enum',
            default => 'unknown',
        };

        $isFinal = $node instanceof Stmt\Class_ && $node->isFinal();

        $extends = null;
        if ($node instanceof Stmt\Class_ && $node->extends !== null) {
            $extends = $node->extends->toString();
        } elseif ($node instanceof Stmt\Interface_ && $node->extends !== []) {
            $extends = implode(',', array_map(fn ($nd) => $nd->toString(), $node->extends));
        }

        $implements = [];
        if ($node instanceof Stmt\Class_ || $node instanceof Stmt\Enum_) {
            $implements = array_map(fn ($nd) => $nd->toString(), $node->implements);
        }

        $traits = [];
        $methods = [];
        $properties = [];
        $constants = [];

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $traits[] = $trait->toString();
                }
            }

            if ($stmt instanceof Stmt\ClassMethod) {
                $methods[] = $this->extractMethodInfo($stmt);
            }

            if ($stmt instanceof Stmt\Property) {
                foreach ($this->extractPropertyInfo($stmt) as $prop) {
                    $properties[] = $prop;
                }
            }

            if ($stmt instanceof Stmt\ClassConst) {
                foreach ($this->extractConstantInfo($stmt) as $const) {
                    $constants[] = $const;
                }
            }

            if ($stmt instanceof Stmt\EnumCase) {
                $valueHash = $stmt->expr !== null ? $this->hasher->hash($stmt->expr) : '';
                $constants[] = new ConstantInfo(
                    name: $stmt->name->toString(),
                    type: null,
                    valueHash: $valueHash,
                    visibility: 'public',
                );
            }
        }

        // Extract promoted properties from constructor
        foreach ($methods as $method) {
            if ($method->name === '__construct') {
                foreach ($method->params as $param) {
                    if (($param['promoted'] ?? false) === true) {
                        $paramName = $param['name'] ?? '';
                        assert(is_string($paramName));
                        $properties[] = new PropertyInfo(
                            name: $paramName,
                            visibility: is_string($param['promotedVisibility'] ?? null) ? $param['promotedVisibility'] : 'public',
                            type: isset($param['type']) && is_string($param['type']) ? $param['type'] : null,
                            defaultHash: isset($param['defaultHash']) && is_string($param['defaultHash']) ? $param['defaultHash'] : null,
                            isStatic: false,
                            isReadonly: (bool) ($param['readonly'] ?? false),
                        );
                    }
                }
            }
        }

        return new ClassInfo(
            name: $name,
            type: $type,
            isFinal: $isFinal,
            extends: $extends,
            implements: $implements,
            traits: $traits,
            methods: $methods,
            properties: $properties,
            constants: $constants,
        );
    }

    private function extractMethodInfo(Stmt\ClassMethod $method): MethodInfo
    {
        $visibility = 'public';
        if ($method->isPrivate()) {
            $visibility = 'private';
        } elseif ($method->isProtected()) {
            $visibility = 'protected';
        }

        $params = $this->extractParams($method->params);
        $returnType = $method->returnType !== null ? $this->typeToString($method->returnType) : null;

        // Hash the method body
        $bodyHash = '';
        if ($method->stmts !== null) {
            $bodyHash = $this->hasher->hash($method->stmts);
        }

        return new MethodInfo(
            name: $method->name->toString(),
            visibility: $visibility,
            isStatic: $method->isStatic(),
            isAbstract: $method->isAbstract(),
            isFinal: $method->isFinal(),
            params: $params,
            returnType: $returnType,
            bodyHash: $bodyHash,
        );
    }

    private function extractFunctionInfo(Stmt\Function_ $function): MethodInfo
    {
        $params = $this->extractParams($function->params);
        $returnType = $function->returnType !== null ? $this->typeToString($function->returnType) : null;

        $bodyHash = $this->hasher->hash($function->stmts);

        return new MethodInfo(
            name: $function->name->toString(),
            visibility: 'public',
            isStatic: false,
            isAbstract: false,
            isFinal: false,
            params: $params,
            returnType: $returnType,
            bodyHash: $bodyHash,
        );
    }

    /**
     * @param Param[] $params
     * @return array<int, array<string, mixed>>
     */
    private function extractParams(array $params): array
    {
        $result = [];
        foreach ($params as $param) {
            assert($param->var instanceof Expr\Variable);
            $varName = $param->var->name;
            $paramInfo = [
                'name' => is_string($varName) ? $varName : $this->hasher->toCanonical($varName),
                'type' => $param->type !== null ? $this->typeToString($param->type) : null,
                'variadic' => $param->variadic,
                'byRef' => $param->byRef,
                'hasDefault' => $param->default !== null,
            ];

            // Check for constructor promotion
            if ($param->flags !== 0) {
                $paramInfo['promoted'] = true;
                $paramInfo['readonly'] = (bool) ($param->flags & Stmt\Class_::MODIFIER_READONLY);

                if (($param->flags & Stmt\Class_::MODIFIER_PUBLIC) !== 0) {
                    $paramInfo['promotedVisibility'] = 'public';
                } elseif (($param->flags & Stmt\Class_::MODIFIER_PROTECTED) !== 0) {
                    $paramInfo['promotedVisibility'] = 'protected';
                } elseif (($param->flags & Stmt\Class_::MODIFIER_PRIVATE) !== 0) {
                    $paramInfo['promotedVisibility'] = 'private';
                }

                if ($param->default !== null) {
                    $paramInfo['defaultHash'] = $this->hasher->hash($param->default);
                }
            }

            $result[] = $paramInfo;
        }

        return $result;
    }

    /**
     * @return PropertyInfo[]
     */
    private function extractPropertyInfo(Stmt\Property $property): array
    {
        $visibility = 'public';
        if ($property->isPrivate()) {
            $visibility = 'private';
        } elseif ($property->isProtected()) {
            $visibility = 'protected';
        }

        $type = $property->type !== null ? $this->typeToString($property->type) : null;
        $results = [];

        foreach ($property->props as $prop) {
            $defaultHash = $prop->default !== null ? $this->hasher->hash($prop->default) : null;

            $results[] = new PropertyInfo(
                name: $prop->name->toString(),
                visibility: $visibility,
                type: $type,
                defaultHash: $defaultHash,
                isStatic: $property->isStatic(),
                isReadonly: $property->isReadonly(),
            );
        }

        return $results;
    }

    /**
     * @return ConstantInfo[]
     */
    private function extractConstantInfo(Stmt\ClassConst $const): array
    {
        $visibility = 'public';
        if ($const->isPrivate()) {
            $visibility = 'private';
        } elseif ($const->isProtected()) {
            $visibility = 'protected';
        }

        $type = $const->type !== null ? $this->typeToString($const->type) : null;
        $results = [];

        $isFinal = $const->isFinal();

        foreach ($const->consts as $c) {
            $results[] = new ConstantInfo(
                name: $c->name->toString(),
                type: $type,
                valueHash: $this->hasher->hash($c->value),
                visibility: $visibility,
                isFinal: $isFinal,
            );
        }

        return $results;
    }

    private function typeToString(Node $type): string
    {
        if ($type instanceof NullableType) {
            return '?' . $this->typeToString($type->type);
        }
        if ($type instanceof UnionType) {
            return implode('|', array_map(fn ($tp) => $this->typeToString($tp), $type->types));
        }
        if ($type instanceof IntersectionType) {
            return implode('&', array_map(fn ($tp) => $this->typeToString($tp), $type->types));
        }
        if ($type instanceof Name) {
            return $type->toString();
        }
        if ($type instanceof Identifier) {
            return $type->toString();
        }

        return $type->getType();
    }

    /**
     * @param Node[] $stmts
     * @param class-string<Stmt\ClassLike>[] $types
     * @return list<Stmt\ClassLike>
     */
    private function findNodes(array $stmts, array $types): array
    {
        /** @var list<Stmt\ClassLike> $found */
        $found = [];
        foreach ($stmts as $stmt) {
            foreach ($types as $type) {
                if ($stmt instanceof $type) {
                    $found[] = $stmt;
                }
            }

            // Search within namespace blocks
            if ($stmt instanceof Stmt\Namespace_ && $stmt->stmts !== null) {
                $found = array_merge($found, $this->findNodes($stmt->stmts, $types));
            }
        }

        return $found;
    }

    /**
     * @param Node[] $stmts
     * @return Stmt\Function_[]
     */
    private function findTopLevelFunctions(array $stmts): array
    {
        $found = [];
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Stmt\Function_) {
                $found[] = $stmt;
            }
            if ($stmt instanceof Stmt\Namespace_ && $stmt->stmts !== null) {
                $found = array_merge($found, $this->findTopLevelFunctions($stmt->stmts));
            }
        }

        return $found;
    }
}
