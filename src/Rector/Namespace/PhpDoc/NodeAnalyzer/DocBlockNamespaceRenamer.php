<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Namespace\PhpDoc\NodeAnalyzer;

use Oro\UpgradeToolkit\Rector\Namespace\NamespaceMatcher;
use Oro\UpgradeToolkit\Rector\Namespace\ValueObject\RenamedNamespace;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\Node as DocNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocNodeVisitor\ChangedPhpDocNodeVisitor;
use Rector\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;

/**
 * Modified copy of \Rector\NodeTypeResolver\PhpDoc\NodeAnalyzer\DocBlockNamespaceRenamer, Rector v0.16.0
 * Includes hasChanged() from \Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo, Rector v0.16.0
 *
 * Copyright (c) 2017-present Tomáš Votruba (https://tomasvotruba.cz)
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 */
class DocBlockNamespaceRenamer
{
    public function __construct(
        private readonly NamespaceMatcher $namespaceMatcher,
        private readonly PhpDocInfoFactory $phpDocInfoFactory,
    ) {
    }

    public function renameFullyQualifiedNamespace($node, array $oldToNewNamespaces): ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $phpDocNodeTraverser = new PhpDocNodeTraverser();
        $phpDocNodeTraverser->traverseWithCallable(
            $phpDocInfo->getPhpDocNode(),
            '',
            function (DocNode $docNode) use ($oldToNewNamespaces): ?DocNode {
                if (!$docNode instanceof IdentifierTypeNode) {
                    return null;
                }
                $trimmedName = \ltrim($docNode->name, '\\');
                if ($docNode->name === $trimmedName) {
                    return null;
                }
                $renamedNamespaceValueObject = $this->namespaceMatcher->matchRenamedNamespace($trimmedName, $oldToNewNamespaces);
                if (!$renamedNamespaceValueObject instanceof RenamedNamespace) {
                    return null;
                }

                return new IdentifierTypeNode('\\' . $renamedNamespaceValueObject->getNameInNewNamespace());
            }
        );

        if (!$this->hasChanged($phpDocInfo)) {
            return null;
        }

        return $node;
    }

    public function hasChanged($phpDocInfo): bool
    {
        if ($phpDocInfo->isNewNode()) {
            return true;
        }
        // has a single node with missing start_end
        $phpDocNodeTraverser = new PhpDocNodeTraverser();
        $changedPhpDocNodeVisitor = new ChangedPhpDocNodeVisitor();
        $phpDocNodeTraverser->addPhpDocNodeVisitor($changedPhpDocNodeVisitor);
        $phpDocNodeTraverser->traverse($phpDocInfo->getPhpDocNode());

        return $changedPhpDocNodeVisitor->hasChanged();
    }
}
