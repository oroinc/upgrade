<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\NodeManipulator;

use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;

/**
 * Modified copy of \Rector\Core\NodeManipulator\ClassInsertManipulator, Rector v0.16.0
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
class ClassInsertManipulator
{
    private const BEFORE_TRAIT_TYPES = [TraitUse::class, Property::class, ClassMethod::class];

    public function addAsFirstTrait(Class_ $class, string $traitName): void
    {
        $traitUse = new TraitUse([new FullyQualified($traitName)]);
        $this->addTraitUse($class, $traitUse);
    }

    private function addTraitUse(Class_ $class, TraitUse $traitUse): void
    {
        foreach (self::BEFORE_TRAIT_TYPES as $type) {
            foreach ($class->stmts as $key => $classStmt) {
                if (!$classStmt instanceof $type) {
                    continue;
                }
                $class->stmts = $this->insertBefore($class->stmts, $traitUse, $key);
                return;
            }
        }
        $class->stmts[] = $traitUse;
    }

    private function insertBefore(array $stmts, Stmt $stmt, int $key): array
    {
        array_splice($stmts, $key, 0, [$stmt]);
        return $stmts;
    }
}
