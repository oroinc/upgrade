<?php

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
