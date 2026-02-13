<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\Serializer;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use Rector\Rector\AbstractRector;

/**
 * Add getSupportedTypes method to classes implementing normalizer interfaces
 *
 * Example:
 * - Adds: public function getSupportedTypes(?string $format): array
 *         {
 *             return ['object' => true];
 *         }
 */
final class AddGetSupportedTypesMethodRector extends AbstractRector
{
    private const SUPPORTED_INTERFACES = [
        'Symfony\\Component\\Serializer\\Normalizer\\ContextAwareDenormalizerInterface',
        'Symfony\\Component\\Serializer\\Normalizer\\ContextAwareNormalizerInterface',
        'Symfony\\Component\\Serializer\\Normalizer\\DenormalizerInterface',
        'Symfony\\Component\\Serializer\\Normalizer\\NormalizerInterface',
    ];

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$this->implementsNormalizerInterface($node)) {
            return null;
        }

        if ($this->hasGetSupportedTypesMethod($node)) {
            return null;
        }

        $method = $this->createGetSupportedTypesMethod();
        $node->stmts[] = $method;

        return $node;
    }

    private function implementsNormalizerInterface(Class_ $class): bool
    {
        if ($class->implements === null) {
            return false;
        }

        foreach ($class->implements as $interface) {
            $interfaceName = $interface->toString();
            if (in_array($interfaceName, self::SUPPORTED_INTERFACES)) {
                return true;
            }
        }

        return false;
    }

    private function hasGetSupportedTypesMethod(Class_ $class): bool
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $this->isName($stmt->name, 'getSupportedTypes')) {
                return true;
            }
        }

        return false;
    }

    private function createGetSupportedTypesMethod(): ClassMethod
    {
        $param = new Param(
            var: new Node\Expr\Variable('format'),
            type: new NullableType(new Name('string'))
        );

        $returnType = new Name('array');

        $docComment = new Doc(
            <<<'DOC'
/**
     * @ToDo: Replaced default return value with more specific if it is needed
     */
DOC
        );

        $returnStmt = new Return_(
            new Array_([
                new ArrayItem(
                    new ConstFetch(new Name('true')),
                    new String_('object')
                )
            ])
        );

        $returnStmt->setDocComment($docComment);

        $method = new ClassMethod(
            new Identifier('getSupportedTypes'),
            [
                'flags' => Class_::MODIFIER_PUBLIC,
                'params' => [$param],
                'returnType' => $returnType,
                'stmts' => [$returnStmt]
            ]
        );

        return $method;
    }
}
