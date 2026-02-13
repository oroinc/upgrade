<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Oro70\Form;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;

/**
 * Adds widget and html5 options to form configureOptions method.
 *
 * Example:
 * - Before: $resolver->setDefaults(['input' => 'array'])
 * - After:  $resolver->setDefaults(['widget' => 'choice', 'html5' => false, 'input' => 'array'])
 */
final class AddFormWidgetAndHtml5OptionsRector extends AbstractRector
{
    private const ABSTRACT_TYPE = 'Symfony\\Component\\Form\\AbstractType';

    private const METHOD_CONFIGURE_OPTIONS = 'configureOptions';

    private const METHOD_SET_DEFAULTS = 'setDefaults';

    private const OPTION_INPUT = 'input';
    private const INPUT_ARRAY = 'array';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    #[\Override]
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    #[\Override]
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if (!$this->isName($node->name, self::METHOD_CONFIGURE_OPTIONS)) {
            return null;
        }

        $scope = ScopeFetcher::fetch($node);
        if (!$this->isAbstractTypeSubclass($scope)) {
            return null;
        }

        $hasChanged = false;

        $this->traverseNodesWithCallable($node->stmts, function (Node $childNode) use (&$hasChanged): ?Node {
            $array = $this->matchSetDefaultsArray($childNode);
            if (!$array instanceof Array_) {
                return null;
            }

            if (!$this->hasInputArrayOption($array)) {
                return null;
            }

            if ($this->prependMissingOptions($array)) {
                $hasChanged = true;
            }

            return null;
        });

        return $hasChanged ? $node : null;
    }

    private function isAbstractTypeSubclass(Scope $scope): bool
    {
        $abstractTypeReflection = $this->reflectionProvider->getClass(self::ABSTRACT_TYPE);

        return (bool) $scope->getClassReflection()?->isSubclassOfClass($abstractTypeReflection);
    }

    private function matchSetDefaultsArray(Node $node): ?Array_
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        if (!$this->isName($node->name, self::METHOD_SET_DEFAULTS)) {
            return null;
        }

        $firstArg = $node->args[0] ?? null;
        if (!$firstArg instanceof Arg) {
            return null;
        }

        return $firstArg->value instanceof Array_ ? $firstArg->value : null;
    }

    private function hasInputArrayOption(Array_ $array): bool
    {
        foreach ($array->items as $item) {
            if (!$item instanceof ArrayItem) {
                continue;
            }

            if (!$item->key instanceof String_ || $item->key->value !== self::OPTION_INPUT) {
                continue;
            }

            return $item->value instanceof String_ && $item->value->value === self::INPUT_ARRAY;
        }

        return false;
    }

    private function prependMissingOptions(Array_ $array): bool
    {
        $existingOptions = $this->getExistingOptionNames($array);

        $itemsToAdd = [];

        if (!isset($existingOptions['widget'])) {
            $itemsToAdd[] = new ArrayItem(new String_('choice'), new String_('widget'));
        }

        if (!isset($existingOptions['html5'])) {
            $itemsToAdd[] = new ArrayItem(new ConstFetch(new Name('false')), new String_('html5'));
        }

        if ([] === $itemsToAdd) {
            return false;
        }

        $array->items = array_merge($itemsToAdd, $array->items);

        return true;
    }

    private function getExistingOptionNames(Array_ $array): array
    {
        $existingOptions = [];

        foreach ($array->items as $item) {
            if (!$item instanceof ArrayItem) {
                continue;
            }

            if (!$item->key instanceof String_) {
                continue;
            }

            $existingOptions[$item->key->value] = true;
        }

        return $existingOptions;
    }
}
