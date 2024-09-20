<?php

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;

return static function (RectorConfig $rectorConfig): void {
    // Transform Oro Constraint annotations to attributes
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        // Constraint Annotations
        new AnnotationToAttribute('Oro\Bundle\CalendarBundle\Validator\Constraints\DateEarlierThan'),
        new AnnotationToAttribute('Oro\Bundle\CalendarBundle\Validator\Constraints\EventAttendees'),
        new AnnotationToAttribute('Oro\Bundle\CalendarBundle\Validator\Constraints\UniqueUid'),
        new AnnotationToAttribute('Oro\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices'),
        new AnnotationToAttribute('Oro\Bundle\RedirectBundle\Validator\Constraints\UrlSafeSlugPrototype'),
        new AnnotationToAttribute('Oro\Bundle\CustomerBundle\Validator\Constraints\FrontendOwner'),
        new AnnotationToAttribute('Oro\Bundle\OrganizationProBundle\Validator\Constraints\Organization'),
        new AnnotationToAttribute('Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted'),
        new AnnotationToAttribute('Oro\Bundle\ApiBundle\Validator\Constraints\All'),
        new AnnotationToAttribute('Oro\Bundle\ApiBundle\Validator\Constraints\HasAdderAndRemover'),
        new AnnotationToAttribute('Oro\Bundle\EntityExtendBundle\Validator\Constraints\Decimal'),
        new AnnotationToAttribute('Oro\Bundle\EntityExtendBundle\Validator\Constraints\EnumOption'),
        new AnnotationToAttribute('Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotPhpKeyword'),
        new AnnotationToAttribute('Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotSqlKeyword'),
        new AnnotationToAttribute('Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueEnumName'),
        new AnnotationToAttribute('Oro\Bundle\FormBundle\Validator\Constraints\EntityClass'),
        new AnnotationToAttribute('Oro\Bundle\FormBundle\Validator\Constraints\HtmlNotBlank'),
        new AnnotationToAttribute('Oro\Bundle\FormBundle\Validator\Constraints\PercentRange'),
        new AnnotationToAttribute('Oro\Bundle\OrganizationBundle\Validator\Constraints\OrganizationUniqueEntity'),
        new AnnotationToAttribute('Oro\Bundle\OrganizationBundle\Validator\Constraints\Owner'),
        new AnnotationToAttribute('Oro\Bundle\OrganizationBundle\Validator\Constraints\ParentBusinessUnit'),
        new AnnotationToAttribute('Oro\Bundle\PlatformBundle\Validator\Constraints\DateEarlierThan'),
        new AnnotationToAttribute('Oro\Bundle\PlatformBundle\Validator\Constraints\ValidLoadedItems'),
    ]);
};
