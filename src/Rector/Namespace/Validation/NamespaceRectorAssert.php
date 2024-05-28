<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Namespace\Validation;

use Rector\Util\StringUtils;
use RectorPrefix202403\Webmozart\Assert\InvalidArgumentException;

/**
 * Modified copy of \Rector\Core\Validation\RectorAssert, Rector v0.16.0
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
class NamespaceRectorAssert
{
    private const NAMESPACE_REGEX = '#^' . self::NAKED_NAMESPACE_REGEX . '$#';

    /**
     * @see https://stackoverflow.com/a/60470526/1348344
     * @see https://regex101.com/r/37aUWA/1
     */
    private const NAKED_NAMESPACE_REGEX = '[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff\\\\]*[a-zA-Z0-9_\\x7f-\\xff]';

    public static function namespaceName(string $name): void
    {
        if (StringUtils::isMatch($name, self::NAMESPACE_REGEX)) {
            return;
        }
        $errorMessage = sprintf('"%s" is not a valid namespace name', $name);
        throw new InvalidArgumentException($errorMessage);
    }
}
