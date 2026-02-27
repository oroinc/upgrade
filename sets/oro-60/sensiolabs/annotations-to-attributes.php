<?php

/**
 * Copy of sensiolabs/annotations-to-attributes.php set, Rector v2.1.2
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
        // @see https://github.com/sensiolabs/SensioFrameworkExtraBundle/pull/707
        new AnnotationToAttribute('Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Cache'),
        new AnnotationToAttribute('Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Entity'),
        new AnnotationToAttribute('Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\IsGranted'),
        new AnnotationToAttribute('Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\ParamConverter'),
        new AnnotationToAttribute('Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Security'),
        new AnnotationToAttribute('Sensio\\Bundle\\FrameworkExtraBundle\\Configuration\\Template'),
    ]);
};
