<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Rules\Namespace;

use Oro\UpgradeToolkit\Rector\Namespace\NamespaceMatcher;
use Oro\UpgradeToolkit\Rector\Namespace\PhpDoc\NodeAnalyzer\DocBlockNamespaceRenamer;
use Oro\UpgradeToolkit\Rector\Namespace\ValueObject\RenamedNamespace;
use Oro\UpgradeToolkit\Rector\PhpParser\AttributeKey;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use RectorPrefix202602\Webmozart\Assert\Assert;

/**
 * Modified copy of \Rector\Renaming\Rector\Namespace_\RenameNamespaceRector, Rector v0.16.0
 *
 *  Copyright (c) 2017-present Tomáš Votruba (https://tomasvotruba.cz)
 *
 *  Permission is hereby granted, free of charge, to any person
 *  obtaining a copy of this software and associated documentation
 *  files (the "Software"), to deal in the Software without
 *  restriction, including without limitation the rights to use,
 *  copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the
 *  Software is furnished to do so, subject to the following
 *  conditions:
 *
 *  The above copyright notice and this permission notice shall be
 *  included in all copies or substantial portions of the Software.
 */
final class RenameNamespaceRector extends AbstractRector implements ConfigurableRectorInterface
{
    private const ONLY_CHANGE_DOCBLOCK_NODE = [
        Property::class,
        ClassMethod::class,
        Function_::class,
        Expression::class,
        Class_::class,
        Interface_::class,
        Trait_::class,
        Enum_::class
    ];

    private array $oldToNewNamespaces = [];
    private array $isChangedInNamespaces = [];
    private readonly NamespaceMatcher $namespaceMatcher;
    private readonly DocBlockNamespaceRenamer $docBlockNamespaceRenamer;

    public function __construct(
        NamespaceMatcher $namespaceMatcher,
        DocBlockNamespaceRenamer $docBlockNamespaceRenamer
    ) {
        $this->namespaceMatcher = $namespaceMatcher;
        $this->docBlockNamespaceRenamer = $docBlockNamespaceRenamer;
    }

    #[\Override]
    public function getNodeTypes(): array
    {
        return array_merge([Namespace_::class, Use_::class, Name::class], self::ONLY_CHANGE_DOCBLOCK_NODE);
    }

    #[\Override]
    public function configure(array $configuration): void
    {
        Assert::allStringNotEmpty(\array_keys($configuration));
        Assert::allStringNotEmpty($configuration);

        $this->oldToNewNamespaces = $configuration;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    #[\Override]
    public function refactor(Node $node): ?Node
    {
        if (in_array(get_class($node), self::ONLY_CHANGE_DOCBLOCK_NODE, true)) {
            /** @var Property|ClassMethod|Function_|Expression|ClassLike $node */
            return $this->docBlockNamespaceRenamer->renameFullyQualifiedNamespace($node, $this->oldToNewNamespaces);
        }
        /** @var Namespace_|Use_|Name $node */
        $name = $this->getName($node);
        if ($name === null) {
            return null;
        }
        $renamedNamespaceValueObject = $this->namespaceMatcher->matchRenamedNamespace($name, $this->oldToNewNamespaces);
        if (!$renamedNamespaceValueObject instanceof RenamedNamespace) {
            return null;
        }
        if ($this->isClassFullyQualifiedName($node)) {
            return null;
        }
        if ($node instanceof Namespace_) {
            $newName = $renamedNamespaceValueObject->getNameInNewNamespace();
            $node->name = new Name($newName);
            $this->isChangedInNamespaces[$newName] = \true;
            return $node;
        }
        if ($node instanceof Use_) {
            $newName = $renamedNamespaceValueObject->getNameInNewNamespace();
            $node->uses[0]->name = new Name($newName);
            return $node;
        }
        $parentNode = $node->getAttribute(AttributeKey::PARENT_NODE);
        // already resolved above
        if ($parentNode instanceof Namespace_) {
            return null;
        }
        if (!$parentNode instanceof UseUse) {
            return $this->processFullyQualified($node, $renamedNamespaceValueObject);
        }
        if ($parentNode->type !== Use_::TYPE_UNKNOWN) {
            return $this->processFullyQualified($node, $renamedNamespaceValueObject);
        }
        return null;
    }

    private function processFullyQualified(Name $name, RenamedNamespace $renamedNamespace): ?FullyQualified
    {
        if (\strncmp($name->toString(), $renamedNamespace->getNewNamespace() . '\\', \strlen($renamedNamespace->getNewNamespace() . '\\')) === 0) {
            return null;
        }
        $nameInNewNamespace = $renamedNamespace->getNameInNewNamespace();
        $values = \array_values($this->oldToNewNamespaces);
        if (!isset($this->isChangedInNamespaces[$nameInNewNamespace])) {
            return new FullyQualified($nameInNewNamespace);
        }
        if (!\in_array($nameInNewNamespace, $values, \true)) {
            return new FullyQualified($nameInNewNamespace);
        }
        return null;
    }
    /**
     * Checks for "new \ClassNoNamespace;"
     * This should be skipped, not a namespace.
     */
    private function isClassFullyQualifiedName(Node $node): bool
    {
        $parentNode = $node->getAttribute(AttributeKey::PARENT_NODE);
        if (!$parentNode instanceof Node) {
            return \false;
        }
        if (!$parentNode instanceof New_) {
            return \false;
        }
        /** @var FullyQualified $fullyQualifiedNode */
        $fullyQualifiedNode = $parentNode->class;
        $newClassName = $fullyQualifiedNode->toString();
        return \array_key_exists($newClassName, $this->oldToNewNamespaces);
    }
}
