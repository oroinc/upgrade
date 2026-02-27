<?php

declare(strict_types=1);

namespace Oro\UpgradeToolkit\Rector\Renaming\ValueObject;

use PHPStan\Type\ObjectType;
use Rector\Validation\RectorAssert;

/**
 * Modifired copy of \Rector\Renaming\ValueObject\RenameProperty Rector v2.1.2
 *
 * Value object representing a property renaming rule configuration.
 * Added the applyTo property to define classes to apply the rule.
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
final class OroRenameProperty
{
    public function __construct(
        private readonly string $type,
        private readonly string $oldProperty,
        private readonly string $newProperty,
        private readonly array $applyTo
    ) {
        RectorAssert::className($type);
        RectorAssert::propertyName($oldProperty);
        RectorAssert::propertyName($newProperty);

        array_walk($applyTo, [RectorAssert::class, 'className']);
    }

    public function getApplyTo(): array
    {
        return $this->applyTo;
    }

    public function getObjectType(): ObjectType
    {
        return new ObjectType($this->type);
    }

    public function getOldProperty(): string
    {
        return $this->oldProperty;
    }

    public function getNewProperty(): string
    {
        return $this->newProperty;
    }
}
