<?php

declare(strict_types=1);

namespace Oro\Rector\Printer;

use Oro\Rector\Application\AddedFilesProcessor;
use Oro\Rector\PhpParser\Node\NodeFinder;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Declare_;
use Rector\PhpParser\Node\CustomNode\FileWithoutNamespace;
use Rector\PhpParser\Printer\BetterStandardPrinter;
use Rector\ValueObject\Application\File;

/**
 * Printer for printing classes next to just-processed one.
 * E.g. in case of extracting class to the same directory, just with different name.
 *
 * Modified copy of \Rector\Core\PhpParser\Printer\NeighbourClassLikePrinter, Rector v0.16.0
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
class NeighbourClassLikePrinter
{
    public function __construct(
        private readonly NodeFinder $nodeFinder,
        private readonly BetterStandardPrinter $betterStandardPrinter,
        private readonly AddedFilesProcessor $addedFilesProcessor,
    ) {
    }

    public function printClassLike(ClassLike $classLike, $mainNode, string $filePath, ?File $file = null): void
    {
        $declares = $this->resolveDeclares($mainNode);
        if ($mainNode instanceof FileWithoutNamespace) {
            $nodesToPrint = \array_merge($declares, [$classLike]);
        } else {
            // Use new class in the namespace
            $mainNode->stmts = [$classLike];
            $nodesToPrint = \array_merge($declares, [$mainNode]);
        }
        $fileDestination = $this->createClassLikeFileDestination($classLike, $filePath);
        $printedFileContent = $this->betterStandardPrinter->prettyPrintFile($nodesToPrint);
        $this->addedFilesProcessor->addFileToAdd($fileDestination, $printedFileContent);
    }

    public function createClassLikeFileDestination(ClassLike $classLike, string $filePath): string
    {
        $currentDirectory = \dirname($filePath);
        return $currentDirectory . \DIRECTORY_SEPARATOR . $classLike->name . '.php';
    }

    private function resolveDeclares($mainNode): array
    {
        $node = $this->nodeFinder->findFirstPreviousOfTypes($mainNode, [Declare_::class]);
        if ($node instanceof Declare_) {
            return [$node];
        }
        return [];
    }
}
