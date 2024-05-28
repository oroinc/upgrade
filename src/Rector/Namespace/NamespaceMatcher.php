<?php

namespace Oro\UpgradeToolkit\Rector\Namespace;

use Oro\UpgradeToolkit\Rector\Namespace\ValueObject\RenamedNamespace;

/**
 * Modified copy of \Rector\Naming\NamespaceMatcher, Rector v0.16.0
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
class NamespaceMatcher
{
    /**
     * @param string[] $oldToNewNamespace
     */
    public function matchRenamedNamespace(string $name, array $oldToNewNamespace): ?RenamedNamespace
    {
        krsort($oldToNewNamespace);
        /** @var string $oldNamespace */
        foreach ($oldToNewNamespace as $oldNamespace => $newNamespace) {
            if ($name === $oldNamespace) {
                return new RenamedNamespace($name, $oldNamespace, $newNamespace);
            }
            if (\strncmp($name, $oldNamespace . '\\', \strlen($oldNamespace . '\\')) === 0) {
                return new RenamedNamespace($name, $oldNamespace, $newNamespace);
            }
        }
        return null;
    }
}
