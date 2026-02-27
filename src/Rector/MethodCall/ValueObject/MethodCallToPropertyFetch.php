<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\MethodCall\ValueObject;

use PHPStan\Type\ObjectType;
use Rector\Validation\RectorAssert;

/**
 * Copy of \Rector\Transform\ValueObject\MethodCallToPropertyFetch, Rector v2.1.2
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
final class MethodCallToPropertyFetch
{
    private readonly string $oldType;
    private readonly string $oldMethod;
    private readonly string $newProperty;

    public function __construct(string $oldType, string $oldMethod, string $newProperty)
    {
        $this->oldType = $oldType;
        $this->oldMethod = $oldMethod;
        $this->newProperty = $newProperty;
        RectorAssert::className($oldType);
        RectorAssert::methodName($oldMethod);
        RectorAssert::propertyName($newProperty);
    }

    public function getOldObjectType(): ObjectType
    {
        return new ObjectType($this->oldType);
    }

    public function getNewProperty(): string
    {
        return $this->newProperty;
    }

    public function getOldMethod(): string
    {
        return $this->oldMethod;
    }
}
