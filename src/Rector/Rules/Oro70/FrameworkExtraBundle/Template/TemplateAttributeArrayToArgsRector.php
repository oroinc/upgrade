<?php

declare (strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\PhpParser\Node\Value\ValueResolver;
use Rector\Rector\AbstractRector;

/**
 * Converts Symfony Template attribute constructor from associative array to arguments.
 *
 * Before:
 * new Template(['template' => '@TestBundle/Default/test.html.twig', 'isStreamable' => true]);
 *
 * After:
 * new Template('@TestBundle/Default/test.html.twig', stream: true);
 */
final class TemplateAttributeArrayToArgsRector extends AbstractRector
{
    private const TEMPLATE_CLASS = 'Symfony\\Bridge\\Twig\\Attribute\\Template';

    private readonly ValueResolver $valueResolver;

    public function __construct(ValueResolver $valueResolver)
    {
        $this->valueResolver = $valueResolver;
    }

    public function getNodeTypes(): array
    {
        return [New_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$this->isTemplateConstructor($node)) {
            return null;
        }

        if (!$node->args) {
            return null;
        }

        foreach ($node->args as $key => $arg) {
            if ($arg->value instanceof Array_) {
                $args = $this->convertArrayToArgs($arg->value);
                unset($node->args[$key]);
                $node->args = $args;
            }
        }

        return $node;
    }

    private function isTemplateConstructor(Node $node): bool
    {
        if (!$node instanceof New_) {
            return false;
        }

        if (!$node->class instanceof FullyQualified && !$node->class instanceof Name) {
            return false;
        }

        $className = $this->getName($node->class);
        if (!\is_string($className)) {
            return false;
        }

        return 0 === \strncmp($className, self::TEMPLATE_CLASS, \strlen(self::TEMPLATE_CLASS));
    }

    private function convertArrayToArgs(Array_ $array): array
    {
        // Empty array replace with ('');
        if (!$array->items) {
            $arg = new Node\Arg(new String_(''));
            return [$arg];
        }

        $args = [];
        foreach ($array->items as $item) {
            if (!$item instanceof ArrayItem) {
                continue;
            }
            if (!$item->key instanceof Expr) {
                // handle nested array
                if ($item->value instanceof New_) {
                    return [];
                }
                continue;
            }

            $keyValue = $this->valueResolver->getValue($item->key);
            if (!\is_string($keyValue)) {
                continue;
            }

            $parameterName = $this->mapParameterName($keyValue);
            $arg = new Node\Arg($item->value);
            $arg->name = new Identifier($parameterName);
            $args[] = $arg;
        }

        if ('template' ===  $args[0]->name->name) {
            $args[0]->name = null;
        }

        return $args;
    }

    private function mapParameterName(string $keyValue): string
    {
        return 'isStreamable' === $keyValue ? 'stream' : $keyValue;
    }
}
