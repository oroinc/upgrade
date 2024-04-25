<?php

namespace Oro\Rector\Namespace\ValueObject;

use Oro\Rector\Namespace\Validation\NamespaceRectorAssert;

/**
 * Modified copy of \Rector\Renaming\ValueObject\RenamedNamespace, Rector v0.16.0
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
class RenamedNamespace
{
    public function __construct(
        private readonly string $currentName,
        private readonly string $oldNamespace,
        private readonly string $newNamespace,
    ) {
        NamespaceRectorAssert::namespaceName($currentName);
        NamespaceRectorAssert::namespaceName($oldNamespace);
        NamespaceRectorAssert::namespaceName($newNamespace);
    }

    public function getNameInNewNamespace(): string
    {
        if ($this->newNamespace === $this->currentName) {
            return $this->currentName;
        }
        return str_replace($this->oldNamespace, $this->newNamespace, $this->currentName);
    }

    public function getNewNamespace(): string
    {
        return $this->newNamespace;
    }
}
