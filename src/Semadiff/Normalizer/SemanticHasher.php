<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Semadiff\Normalizer;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Node\UnionType;
use PhpParser\Node\VariadicPlaceholder;

/**
 * Recursively hashes AST nodes WITH their semantic content.
 * This is the critical piece that fixes the previous AST approach where
 * node *types* were compared but not *content* (e.g., getId() vs getInternalId()
 * were seen as identical MethodCall nodes).
 */
final class SemanticHasher
{
    /**
     * @param Node|Node[]|null $node
     */
    public function hash(Node|array|null $node): string
    {
        $canonical = $this->toCanonical($node);

        return md5($canonical);
    }

    /**
     * @param Node|Node[]|null $node
     */
    public function toCanonical(Node|array|null $node): string
    {
        if ($node === null) {
            return 'NULL';
        }

        if (is_array($node)) {
            $parts = array_map(fn ($item) => $this->toCanonical($item), $node);
            return implode(';', $parts);
        }

        return $this->nodeToString($node);
    }

    /** @SuppressWarnings("NPathComplexity") */
    private function nodeToString(Node $node): string
    {
        // Method calls: ->methodName(args)
        if ($node instanceof Expr\MethodCall) {
            return $this->toCanonical($node->var)
                . '->' . $this->identifierToString($node->name)
                . '(' . $this->argsToString($node->args) . ')';
        }

        // Static calls: ClassName::methodName(args)
        if ($node instanceof Expr\StaticCall) {
            return $this->nameToString($node->class)
                . '::' . $this->identifierToString($node->name)
                . '(' . $this->argsToString($node->args) . ')';
        }

        // Nullsafe method calls: ?->methodName(args)
        if ($node instanceof Expr\NullsafeMethodCall) {
            return $this->toCanonical($node->var)
                . '?->' . $this->identifierToString($node->name)
                . '(' . $this->argsToString($node->args) . ')';
        }

        // Function calls: functionName(args)
        if ($node instanceof Expr\FuncCall) {
            $name = $node->name instanceof Name
                ? ltrim($node->name->toString(), '\\')
                : $this->toCanonical($node->name);
            return $name . '(' . $this->argsToString($node->args) . ')';
        }

        // Variables
        if ($node instanceof Expr\Variable) {
            $name = $node->name;
            if ($name instanceof Expr) {
                return '${' . $this->toCanonical($name) . '}';
            }
            return '$' . $name;
        }

        // Property fetch: $obj->property
        if ($node instanceof Expr\PropertyFetch) {
            return $this->toCanonical($node->var) . '->' . $this->identifierToString($node->name);
        }

        // Nullsafe property fetch: $obj?->property
        if ($node instanceof Expr\NullsafePropertyFetch) {
            return $this->toCanonical($node->var) . '?->' . $this->identifierToString($node->name);
        }

        // Static property fetch: ClassName::$property
        if ($node instanceof Expr\StaticPropertyFetch) {
            return $this->nameToString($node->class) . '::$' . $this->identifierToString($node->name);
        }

        // Class constant fetch: ClassName::CONSTANT
        if ($node instanceof Expr\ClassConstFetch) {
            return $this->nameToString($node->class) . '::' . $this->identifierToString($node->name);
        }

        // Constant fetch (global): CONSTANT_NAME
        if ($node instanceof Expr\ConstFetch) {
            return ltrim($node->name->toString(), '\\');
        }

        // String literals
        if ($node instanceof Scalar\String_) {
            return '"' . addslashes($node->value) . '"';
        }

        // Encapsed string (interpolated)
        if ($node instanceof Scalar\InterpolatedString) {
            $parts = array_map(fn ($part) => $this->toCanonical($part), $node->parts);
            return '"' . implode('', $parts) . '"';
        }

        // String part in interpolated strings
        if ($node instanceof InterpolatedStringPart) {
            return addslashes($node->value);
        }

        // Integer literals
        if ($node instanceof Scalar\Int_) {
            return (string) $node->value;
        }

        // Float literals
        if ($node instanceof Scalar\Float_) {
            return (string) $node->value;
        }

        // Names (class names, etc.)
        if ($node instanceof Name\FullyQualified) {
            return ltrim($node->toString(), '\\');
        }
        if ($node instanceof Name) {
            return $node->toString();
        }

        // Identifiers
        if ($node instanceof Identifier) {
            return $node->toString();
        }

        // Assignment
        if ($node instanceof Expr\Assign) {
            return $this->toCanonical($node->var) . '=' . $this->toCanonical($node->expr);
        }

        // Compound assignments (+=, -=, .=, etc.)
        if ($node instanceof Expr\AssignOp) {
            $op = $this->getAssignOpSymbol($node);
            return $this->toCanonical($node->var) . $op . $this->toCanonical($node->expr);
        }

        // Binary operations
        if ($node instanceof Expr\BinaryOp) {
            $op = $this->getBinaryOpSymbol($node);
            return '(' . $this->toCanonical($node->left) . $op . $this->toCanonical($node->right) . ')';
        }

        // Unary operations
        if ($node instanceof Expr\UnaryMinus) {
            return '(-' . $this->toCanonical($node->expr) . ')';
        }
        if ($node instanceof Expr\UnaryPlus) {
            return '(+' . $this->toCanonical($node->expr) . ')';
        }
        if ($node instanceof Expr\BooleanNot) {
            return '(!' . $this->toCanonical($node->expr) . ')';
        }
        if ($node instanceof Expr\BitwiseNot) {
            return '(~' . $this->toCanonical($node->expr) . ')';
        }
        if ($node instanceof Expr\PreInc) {
            return '(++' . $this->toCanonical($node->var) . ')';
        }
        if ($node instanceof Expr\PreDec) {
            return '(--' . $this->toCanonical($node->var) . ')';
        }
        if ($node instanceof Expr\PostInc) {
            return '(' . $this->toCanonical($node->var) . '++)';
        }
        if ($node instanceof Expr\PostDec) {
            return '(' . $this->toCanonical($node->var) . '--)';
        }

        // New expression
        if ($node instanceof Expr\New_) {
            return 'new ' . $this->nameToString($node->class) . '(' . $this->argsToString($node->args) . ')';
        }

        // Instanceof
        if ($node instanceof Expr\Instanceof_) {
            return $this->toCanonical($node->expr) . ' instanceof ' . $this->nameToString($node->class);
        }

        // Array
        if ($node instanceof Expr\Array_) {
            $items = array_map(function (Node\ArrayItem $item) {
                $key = $item->key !== null ? $this->toCanonical($item->key) . '=>' : '';
                $unpack = $item->unpack ? '...' : '';
                return $key . $unpack . $this->toCanonical($item->value);
            }, $node->items);
            return '[' . implode(',', $items) . ']';
        }

        // Array dimension fetch: $arr[$key]
        if ($node instanceof Expr\ArrayDimFetch) {
            $dim = $node->dim !== null ? $this->toCanonical($node->dim) : '';
            return $this->toCanonical($node->var) . '[' . $dim . ']';
        }

        // Ternary
        if ($node instanceof Expr\Ternary) {
            $if = $node->if !== null ? $this->toCanonical($node->if) : '';
            return '(' . $this->toCanonical($node->cond) . '?' . $if . ':' . $this->toCanonical($node->else) . ')';
        }

        // Cast expressions
        if ($node instanceof Expr\Cast\Int_) {
            return '(int)' . $this->toCanonical($node->expr);
        }
        if ($node instanceof Expr\Cast\Double) {
            return '(float)' . $this->toCanonical($node->expr);
        }
        if ($node instanceof Expr\Cast\String_) {
            return '(string)' . $this->toCanonical($node->expr);
        }
        if ($node instanceof Expr\Cast\Bool_) {
            return '(bool)' . $this->toCanonical($node->expr);
        }
        if ($node instanceof Expr\Cast\Array_) {
            return '(array)' . $this->toCanonical($node->expr);
        }
        if ($node instanceof Expr\Cast\Object_) {
            return '(object)' . $this->toCanonical($node->expr);
        }
        if ($node instanceof Expr\Cast\Unset_) {
            return '(unset)' . $this->toCanonical($node->expr);
        }

        // Closure / Arrow function
        if ($node instanceof Expr\Closure) {
            $params = $this->paramsToString($node->params);
            $uses = array_map(function (Expr\ClosureUse $use) {
                $name = $use->var->name;
                return ($use->byRef ? '&' : '') . '$' . (is_string($name) ? $name : $this->toCanonical($name));
            }, $node->uses);
            $usesStr = $uses !== [] ? ' use(' . implode(',', $uses) . ')' : '';
            $static = $node->static ? 'static ' : '';
            $ret = $node->returnType !== null ? ':' . $this->typeToString($node->returnType) : '';
            $body = $this->toCanonical($node->stmts);
            return $static . 'function(' . $params . ')' . $usesStr . $ret . '{' . $body . '}';
        }

        if ($node instanceof Expr\ArrowFunction) {
            $params = $this->paramsToString($node->params);
            $static = $node->static ? 'static ' : '';
            $ret = $node->returnType !== null ? ':' . $this->typeToString($node->returnType) : '';
            return $static . 'fn(' . $params . ')' . $ret . '=>' . $this->toCanonical($node->expr);
        }

        // Yield
        if ($node instanceof Expr\Yield_) {
            $key = $node->key !== null ? $this->toCanonical($node->key) . '=>' : '';
            $val = $node->value !== null ? $this->toCanonical($node->value) : '';
            return 'yield ' . $key . $val;
        }

        if ($node instanceof Expr\YieldFrom) {
            return 'yield from ' . $this->toCanonical($node->expr);
        }

        // Clone
        if ($node instanceof Expr\Clone_) {
            return 'clone ' . $this->toCanonical($node->expr);
        }

        // Throw expression (PHP 8)
        if ($node instanceof Expr\Throw_) {
            return 'throw ' . $this->toCanonical($node->expr);
        }

        // Match expression
        if ($node instanceof Expr\Match_) {
            $arms = array_map(fn ($arm) => $this->toCanonical($arm), $node->arms);
            return 'match(' . $this->toCanonical($node->cond) . '){' . implode(';', $arms) . '}';
        }

        if ($node instanceof MatchArm) {
            $conds = $node->conds !== null
                ? implode(',', array_map(fn ($cd) => $this->toCanonical($cd), $node->conds))
                : 'default';
            return $conds . '=>' . $this->toCanonical($node->body);
        }

        // Empty, Isset, Print, Exit
        if ($node instanceof Expr\Empty_) {
            return 'empty(' . $this->toCanonical($node->expr) . ')';
        }
        if ($node instanceof Expr\Isset_) {
            return 'isset(' . implode(',', array_map(fn ($vr) => $this->toCanonical($vr), $node->vars)) . ')';
        }
        if ($node instanceof Expr\Print_) {
            return 'print(' . $this->toCanonical($node->expr) . ')';
        }
        if ($node instanceof Expr\Exit_) {
            $expr = $node->expr !== null ? $this->toCanonical($node->expr) : '';
            return 'exit(' . $expr . ')';
        }

        // List assignment
        if ($node instanceof Expr\List_) {
            $items = array_map(function ($item) {
                if ($item === null) {
                    return '';
                }
                $key = $item->key !== null ? $this->toCanonical($item->key) . '=>' : '';
                return $key . $this->toCanonical($item->value);
            }, $node->items);
            return 'list(' . implode(',', $items) . ')';
        }

        // Error suppress
        if ($node instanceof Expr\ErrorSuppress) {
            return '@' . $this->toCanonical($node->expr);
        }

        // Eval
        if ($node instanceof Expr\Eval_) {
            return 'eval(' . $this->toCanonical($node->expr) . ')';
        }

        // Include/Require
        if ($node instanceof Expr\Include_) {
            $types = [
                Expr\Include_::TYPE_INCLUDE => 'include',
                Expr\Include_::TYPE_INCLUDE_ONCE => 'include_once',
                Expr\Include_::TYPE_REQUIRE => 'require',
                Expr\Include_::TYPE_REQUIRE_ONCE => 'require_once',
            ];
            return ($types[$node->type] ?? 'include') . '(' . $this->toCanonical($node->expr) . ')';
        }

        // Assign reference
        if ($node instanceof Expr\AssignRef) {
            return $this->toCanonical($node->var) . '=&' . $this->toCanonical($node->expr);
        }

        // Statements
        if ($node instanceof Stmt\Return_) {
            $expr = $node->expr !== null ? $this->toCanonical($node->expr) : '';
            return 'return ' . $expr;
        }

        if ($node instanceof Stmt\If_) {
            $result = 'if(' . $this->toCanonical($node->cond) . '){' . $this->toCanonical($node->stmts) . '}';
            foreach ($node->elseifs as $elseif) {
                $result .= 'elseif(' . $this->toCanonical($elseif->cond) . '){' . $this->toCanonical($elseif->stmts) . '}';
            }
            if ($node->else !== null) {
                $result .= 'else{' . $this->toCanonical($node->else->stmts) . '}';
            }
            return $result;
        }

        if ($node instanceof Stmt\For_) {
            $init = implode(',', array_map(fn ($ex) => $this->toCanonical($ex), $node->init));
            $cond = implode(',', array_map(fn ($ex) => $this->toCanonical($ex), $node->cond));
            $loop = implode(',', array_map(fn ($ex) => $this->toCanonical($ex), $node->loop));
            return 'for(' . $init . ';' . $cond . ';' . $loop . '){' . $this->toCanonical($node->stmts) . '}';
        }

        if ($node instanceof Stmt\Foreach_) {
            $keyPart = $node->keyVar !== null ? $this->toCanonical($node->keyVar) . '=>' : '';
            $byRef = $node->byRef ? '&' : '';
            return 'foreach(' . $this->toCanonical($node->expr) . ' as ' . $keyPart . $byRef . $this->toCanonical($node->valueVar) . '){' . $this->toCanonical($node->stmts) . '}';
        }

        if ($node instanceof Stmt\While_) {
            return 'while(' . $this->toCanonical($node->cond) . '){' . $this->toCanonical($node->stmts) . '}';
        }

        if ($node instanceof Stmt\Do_) {
            return 'do{' . $this->toCanonical($node->stmts) . '}while(' . $this->toCanonical($node->cond) . ')';
        }

        if ($node instanceof Stmt\Switch_) {
            $cases = array_map(function ($case) {
                $cond = $case->cond !== null ? $this->toCanonical($case->cond) : 'default';
                return 'case ' . $cond . ':' . $this->toCanonical($case->stmts);
            }, $node->cases);
            return 'switch(' . $this->toCanonical($node->cond) . '){' . implode('', $cases) . '}';
        }

        if ($node instanceof Stmt\TryCatch) {
            $result = 'try{' . $this->toCanonical($node->stmts) . '}';
            foreach ($node->catches as $catch) {
                $types = implode('|', array_map(fn ($tp) => $this->nameToString($tp), $catch->types));
                $var = $catch->var !== null ? $this->toCanonical($catch->var) : '';
                $result .= 'catch(' . $types . ' ' . $var . '){' . $this->toCanonical($catch->stmts) . '}';
            }
            if ($node->finally !== null) {
                $result .= 'finally{' . $this->toCanonical($node->finally->stmts) . '}';
            }
            return $result;
        }

        if ($node instanceof Stmt\Expression) {
            return $this->toCanonical($node->expr);
        }

        if ($node instanceof Stmt\Echo_) {
            return 'echo ' . implode(',', array_map(fn ($ex) => $this->toCanonical($ex), $node->exprs));
        }

        if ($node instanceof Stmt\Unset_) {
            return 'unset(' . implode(',', array_map(fn ($vr) => $this->toCanonical($vr), $node->vars)) . ')';
        }

        if ($node instanceof Stmt\Break_) {
            $num = $node->num !== null ? ' ' . $this->toCanonical($node->num) : '';
            return 'break' . $num;
        }

        if ($node instanceof Stmt\Continue_) {
            $num = $node->num !== null ? ' ' . $this->toCanonical($node->num) : '';
            return 'continue' . $num;
        }

        if ($node instanceof Stmt\Nop) {
            return '';
        }

        if ($node instanceof Stmt\Global_) {
            return 'global ' . implode(',', array_map(fn ($vr) => $this->toCanonical($vr), $node->vars));
        }

        if ($node instanceof Stmt\Static_) {
            $vars = array_map(function ($var) {
                $default = $var->default !== null ? '=' . $this->toCanonical($var->default) : '';
                return $this->toCanonical($var->var) . $default;
            }, $node->vars);
            return 'static ' . implode(',', $vars);
        }

        // Inline HTML
        if ($node instanceof Stmt\InlineHTML) {
            return 'INLINE_HTML(' . md5($node->value) . ')';
        }

        // Label/Goto
        if ($node instanceof Stmt\Label) {
            return 'label:' . $node->name;
        }
        if ($node instanceof Stmt\Goto_) {
            return 'goto ' . $node->name;
        }

        // Named argument in Arg node
        if ($node instanceof Arg) {
            $name = $node->name !== null ? $node->name->toString() . ':' : '';
            $unpack = $node->unpack ? '...' : '';
            $byRef = $node->byRef ? '&' : '';
            return $name . $unpack . $byRef . $this->toCanonical($node->value);
        }

        // VariadicPlaceholder (first-class callables: strlen(...))
        if ($node instanceof VariadicPlaceholder) {
            return '...';
        }

        // Param node (for closures/arrow functions)
        if ($node instanceof Param) {
            return $this->paramToString($node);
        }

        // Declare statement
        if ($node instanceof Stmt\Declare_) {
            // Skip declare(strict_types=1) as cosmetic
            return '';
        }

        // Namespace
        if ($node instanceof Stmt\Namespace_) {
            $name = $node->name !== null ? $node->name->toString() : '';
            return 'namespace ' . $name . '{' . $this->toCanonical($node->stmts) . '}';
        }

        // Use statements
        if ($node instanceof Stmt\Use_) {
            $uses = array_map(fn ($use) => $use->name->toString(), $node->uses);
            return 'use ' . implode(',', $uses);
        }

        if ($node instanceof Stmt\GroupUse) {
            $prefix = $node->prefix->toString();
            $uses = array_map(fn ($use) => $use->name->toString(), $node->uses);
            return 'use ' . $prefix . '\\{' . implode(',', $uses) . '}';
        }

        // Fallback: use node type + recursively hash sub-nodes
        return $this->fallbackNodeToString($node);
    }

    private function fallbackNodeToString(Node $node): string
    {
        $type = $node->getType();
        $parts = [$type];

        foreach ($node->getSubNodeNames() as $name) {
            $subNode = $node->$name; // @phpstan-ignore property.dynamicName
            if ($subNode === null) {
                continue;
            }
            if ($subNode instanceof Node) {
                $parts[] = $name . ':' . $this->toCanonical($subNode);
            } elseif (is_array($subNode)) {
                $arrayParts = [];
                foreach ($subNode as $item) {
                    if ($item instanceof Node) {
                        $arrayParts[] = $this->toCanonical($item);
                    } elseif (is_scalar($item)) {
                        $arrayParts[] = (string) $item;
                    }
                }
                if ($arrayParts !== []) {
                    $parts[] = $name . ':[' . implode(',', $arrayParts) . ']';
                }
            } elseif (is_scalar($subNode)) {
                $parts[] = $name . ':' . (string) $subNode;
            }
        }

        return implode('|', $parts);
    }

    private function identifierToString(Node|string $node): string
    {
        if ($node instanceof Identifier) {
            return $node->toString();
        }
        if ($node instanceof Expr) {
            return '{' . $this->toCanonical($node) . '}';
        }
        if (is_string($node)) {
            return $node;
        }

        return $this->toCanonical($node);
    }

    private function nameToString(Node $node): string
    {
        if ($node instanceof Name\FullyQualified) {
            return ltrim($node->toString(), '\\');
        }
        if ($node instanceof Name) {
            return $node->toString();
        }
        if ($node instanceof Identifier) {
            return $node->toString();
        }

        return $this->toCanonical($node);
    }

    /**
     * @param array<Arg|VariadicPlaceholder> $args
     */
    private function argsToString(array $args): string
    {
        return implode(',', array_map(fn ($arg) => $this->toCanonical($arg), $args));
    }

    /**
     * @param Param[] $params
     */
    private function paramsToString(array $params): string
    {
        return implode(',', array_map(fn ($pm) => $this->paramToString($pm), $params));
    }

    private function paramToString(Param $param): string
    {
        $type = $param->type !== null ? $this->typeToString($param->type) . ' ' : '';
        $variadic = $param->variadic ? '...' : '';
        $byRef = $param->byRef ? '&' : '';
        $name = $param->var instanceof Expr\Variable
            ? '$' . (is_string($param->var->name) ? $param->var->name : $this->toCanonical($param->var->name))
            : $this->toCanonical($param->var);
        $default = $param->default !== null ? '=' . $this->toCanonical($param->default) : '';

        return $type . $byRef . $variadic . $name . $default;
    }

    private function typeToString(Node $type): string
    {
        if ($type instanceof NullableType) {
            return '?' . $this->typeToString($type->type);
        }
        if ($type instanceof UnionType) {
            $parts = array_map(fn ($tp) => $this->typeToString($tp), $type->types);
            return implode('|', $parts);
        }
        if ($type instanceof IntersectionType) {
            $parts = array_map(fn ($tp) => $this->typeToString($tp), $type->types);
            return implode('&', $parts);
        }
        if ($type instanceof Name\FullyQualified) {
            return ltrim($type->toString(), '\\');
        }
        if ($type instanceof Name) {
            return $type->toString();
        }
        if ($type instanceof Identifier) {
            return $type->toString();
        }

        return $this->toCanonical($type);
    }

    private function getAssignOpSymbol(Expr\AssignOp $node): string
    {
        return match (true) {
            $node instanceof Expr\AssignOp\Plus => '+=',
            $node instanceof Expr\AssignOp\Minus => '-=',
            $node instanceof Expr\AssignOp\Mul => '*=',
            $node instanceof Expr\AssignOp\Div => '/=',
            $node instanceof Expr\AssignOp\Mod => '%=',
            $node instanceof Expr\AssignOp\Concat => '.=',
            $node instanceof Expr\AssignOp\BitwiseAnd => '&=',
            $node instanceof Expr\AssignOp\BitwiseOr => '|=',
            $node instanceof Expr\AssignOp\BitwiseXor => '^=',
            $node instanceof Expr\AssignOp\ShiftLeft => '<<=',
            $node instanceof Expr\AssignOp\ShiftRight => '>>=',
            $node instanceof Expr\AssignOp\Pow => '**=',
            $node instanceof Expr\AssignOp\Coalesce => '??=',
            default => '?=',
        };
    }

    private function getBinaryOpSymbol(Expr\BinaryOp $node): string
    {
        return match (true) {
            $node instanceof Expr\BinaryOp\Plus => '+',
            $node instanceof Expr\BinaryOp\Minus => '-',
            $node instanceof Expr\BinaryOp\Mul => '*',
            $node instanceof Expr\BinaryOp\Div => '/',
            $node instanceof Expr\BinaryOp\Mod => '%',
            $node instanceof Expr\BinaryOp\Pow => '**',
            $node instanceof Expr\BinaryOp\Concat => '.',
            $node instanceof Expr\BinaryOp\BooleanAnd => '&&',
            $node instanceof Expr\BinaryOp\BooleanOr => '||',
            $node instanceof Expr\BinaryOp\LogicalAnd => 'and',
            $node instanceof Expr\BinaryOp\LogicalOr => 'or',
            $node instanceof Expr\BinaryOp\LogicalXor => 'xor',
            $node instanceof Expr\BinaryOp\BitwiseAnd => '&',
            $node instanceof Expr\BinaryOp\BitwiseOr => '|',
            $node instanceof Expr\BinaryOp\BitwiseXor => '^',
            $node instanceof Expr\BinaryOp\ShiftLeft => '<<',
            $node instanceof Expr\BinaryOp\ShiftRight => '>>',
            $node instanceof Expr\BinaryOp\Equal => '==',
            $node instanceof Expr\BinaryOp\NotEqual => '!=',
            $node instanceof Expr\BinaryOp\Identical => '===',
            $node instanceof Expr\BinaryOp\NotIdentical => '!==',
            $node instanceof Expr\BinaryOp\Greater => '>',
            $node instanceof Expr\BinaryOp\GreaterOrEqual => '>=',
            $node instanceof Expr\BinaryOp\Smaller => '<',
            $node instanceof Expr\BinaryOp\SmallerOrEqual => '<=',
            $node instanceof Expr\BinaryOp\Spaceship => '<=>',
            $node instanceof Expr\BinaryOp\Coalesce => '??',
            default => '?op?',
        };
    }
}
