<?php

/**
 * Copy of fosrest/annotations-to-attributes.php set, Rector v2.1.2
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

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        // @see https://github.com/FriendsOfSymfony/FOSRestBundle/pull/2325
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Copy'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Delete'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Get'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Head'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Link'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Lock'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Mkcol'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Move'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Options'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Patch'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Post'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\PropFind'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\PropPatch'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Put'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Route'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Unlink'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\Unlock'),
        // @see https://github.com/FriendsOfSymfony/FOSRestBundle/pull/2326
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\View'),
        // @see https://github.com/FriendsOfSymfony/FOSRestBundle/pull/2327
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\FileParam'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\QueryParam'),
        new AnnotationToAttribute('FOS\\RestBundle\\Controller\\Annotations\\RequestParam'),
    ]);
};
