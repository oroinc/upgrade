<?php

/**
 * Modified copy of symfony-code-quality set, Rector v2.1.2
 * (Removed rules used in previous sets)
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

declare (strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\CodeQuality\Rector\AttributeGroup\SingleConditionSecurityAttributeToIsGrantedRector;
use Rector\Symfony\CodeQuality\Rector\BinaryOp\RequestIsMainRector;
use Rector\Symfony\CodeQuality\Rector\Class_\InlineClassRoutePrefixRector;
use Rector\Symfony\CodeQuality\Rector\Class_\SplitAndSecurityAttributeToIsGrantedRector;
use Rector\Symfony\CodeQuality\Rector\MethodCall\AssertSameResponseCodeWithDebugContentsRector;
use Rector\Symfony\CodeQuality\Rector\MethodCall\StringCastDebugResponseRector;
use Rector\Symfony\Symfony26\Rector\MethodCall\RedirectToRouteRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rules([
        RedirectToRouteRector::class,
        // request method
        RequestIsMainRector::class,
        // tests
        AssertSameResponseCodeWithDebugContentsRector::class,
        StringCastDebugResponseRector::class,
        // routing
        InlineClassRoutePrefixRector::class,
        // narrow attributes
        SingleConditionSecurityAttributeToIsGrantedRector::class,
        SplitAndSecurityAttributeToIsGrantedRector::class,
    ]);
};
