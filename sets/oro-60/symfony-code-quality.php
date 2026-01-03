<?php

/**
 * Modified copy of symfony-code-quality set, Rector v1.0.3
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

use Rector\Config\RectorConfig;
use Rector\Symfony\CodeQuality\Rector\BinaryOp\ResponseStatusCodeRector;
use Rector\Symfony\CodeQuality\Rector\Class_\EventListenerToEventSubscriberRector;
use Rector\Symfony\CodeQuality\Rector\Class_\LoadValidatorMetadataToAnnotationRector;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\ParamTypeFromRouteRequiredRegexRector;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\RemoveUnusedRequestParamRector;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\ResponseReturnTypeControllerActionRector;
use Rector\Symfony\CodeQuality\Rector\MethodCall\LiteralGetToRequestClassConstantRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rules([
        // Doesn't work because of a bug in ORO
        //        MakeCommandLazyRector::class,
        EventListenerToEventSubscriberRector::class,
        ResponseReturnTypeControllerActionRector::class,
        // int and string literals to const fetches
        ResponseStatusCodeRector::class,
        LiteralGetToRequestClassConstantRector::class,
        RemoveUnusedRequestParamRector::class,
        ParamTypeFromRouteRequiredRegexRector::class,
        // Is not required to upgrade so it's skipped to reduce required refactoring
        //        ActionSuffixRemoverRector::class,
        LoadValidatorMetadataToAnnotationRector::class,
    ]);
};
