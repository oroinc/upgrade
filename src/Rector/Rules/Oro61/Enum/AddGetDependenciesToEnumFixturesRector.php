<?php

namespace Oro\UpgradeToolkit\Rector\Rules\Oro61\Enum;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\Rector\AbstractRector;

/**
 * Adds missing LoadLanguageData dependency
 * to fixture classes that use createEnumOption() or createEnumValue().
 */
class AddGetDependenciesToEnumFixturesRector extends AbstractRector
{
    public function __construct(
        private readonly BetterNodeFinder $betterNodeFinder
    ) {
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Class_) {
            return null;
        }

        if (!$node->extends instanceof Name) {
            return null;
        }

        if ($this->getName($node->extends) !== 'Doctrine\Common\DataFixtures\AbstractFixture') {
            return null;
        }

        $callsCreateEnumOption = false;
        foreach ($node->getMethods() as $method) {
            if ($this->callsCreateEnumOptionOrCreateEnumValue($method)) {
                $callsCreateEnumOption = true;

                break;
            }
        }

        if (!$callsCreateEnumOption) {
            return null;
        }

        foreach ($node->getMethods() as $method) {
            if ('getDependencies' === $this->getName($method)) {
                $this->ensureDependencyPresent($method);

                return $node;
            }
        }

        $method = new ClassMethod('getDependencies', [
            'flags' => Class_::MODIFIER_PUBLIC,
            'returnType' => new Name('array'),
            'stmts' => [new Return_(
                new Array_([
                    new ArrayItem(new ClassConstFetch(
                        new FullyQualified('Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData'),
                        'class'
                    ))
                ])
            )],
        ]);

        $method->setDocComment(new Doc(<<<'PHPDOC'
/**
 * It is required to ensure languages are loaded before enum options are created.
 */
PHPDOC));

        $method->attrGroups = [
            new AttributeGroup([
                new Attribute(new FullyQualified('Override')),
            ])
        ];

        $node->stmts[] = $method;

        return $node;
    }

    private function callsCreateEnumOptionOrCreateEnumValue(ClassMethod $method): bool
    {
        if ($method->stmts === null) {
            return false;
        }

        foreach ($method->stmts as $stmt) {
            if ($this->containsCreateEnumOptionOrCreateEnumValue($stmt)) {
                return true;
            }
        }

        return false;
    }

    private function containsCreateEnumOptionOrCreateEnumValue(Node $node): bool
    {
        /** @var MethodCall[] $methodCalls */
        $methodCalls = $this->betterNodeFinder->findInstanceOf($node, MethodCall::class);

        foreach ($methodCalls as $methodCall) {
            if (
                $this->isName($methodCall->name, 'createEnumOption')
                || $this->isName($methodCall->name, 'createEnumValue')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function ensureDependencyPresent(ClassMethod $method): void
    {
        if ($method->stmts === null || count($method->stmts) === 0) {
            return;
        }

        $stmt = $method->stmts[0];
        if (!$stmt instanceof Return_ || !$stmt->expr instanceof Array_) {
            return;
        }

        $items = $stmt->expr->items;

        foreach ($items as $item) {
            if (!$item instanceof ArrayItem) {
                continue;
            }

            $value = $item->value;
            if ($value instanceof ClassConstFetch
                && $value->class instanceof FullyQualified
                && $value->class->toString() === 'Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData'
            ) {
                return;
            }
        }

        $stmt->expr->items[] = new ArrayItem(new ClassConstFetch(
            new FullyQualified('Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData'),
            'class'
        ));

        $hasOverride = false;
        foreach ($method->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name instanceof FullyQualified && $attr->name->toString() === 'Override') {
                    $hasOverride = true;

                    break 2;
                }
            }
        }

        if (!$hasOverride) {
            $method->attrGroups[] = new AttributeGroup([
                new Attribute(new FullyQualified('Override')),
            ]);
        }

        if ($method->getDocComment() === null) {
            $method->setDocComment(new Doc(<<<'PHPDOC'
/**
 * Adds required dependency to ensure enum options are created after loading languages.
 */
PHPDOC));
        }
    }
}
