<?php

use Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;

return static function (RectorConfig $rectorConfig): void {
    // Transform custom annotations to attributes
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        // Config Annotations
        new AnnotationToAttribute('Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config'),
        new AnnotationToAttribute('Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField'),
        // Acl Annotations
        new AnnotationToAttribute('Oro\Bundle\SecurityBundle\Attribute\Acl'),
        new AnnotationToAttribute('Oro\Bundle\SecurityBundle\Attribute\AclAncestor'),
        new AnnotationToAttribute('Oro\Bundle\SecurityBundle\Attribute\CsrfProtection'),
        // Title Template Annotations
        new AnnotationToAttribute('Oro\Bundle\NavigationBundle\Attribute\TitleTemplate'),
        // Layout Annotations
        new AnnotationToAttribute('Oro\Bundle\LayoutBundle\Attribute\Layout'),
        // Discriminator Value Annotations
        new AnnotationToAttribute('Oro\Bundle\EntityExtendBundle\Attribute\ORM\DiscriminatorValue'),
        // Help  Annotations
        new AnnotationToAttribute('Oro\Bundle\HelpBundle\Attribute\Help'),
        // Demo Data Annotations
        new AnnotationToAttribute('Oro\Bundle\DemoDataCRMProBundle\Fake\NamePrefix'),
        new AnnotationToAttribute('Oro\Bundle\DemoDataCRMProBundle\Fake\RouteResource'),
    ]);

    // Change namespaces.
    // E.g: 'old\namespace' => 'new\namespace'
    $rectorConfig->ruleWithConfiguration(RenameNamespaceRector::class, [
        'Oro\Bundle\EntityConfigBundle\Metadata\Annotation' => 'Oro\Bundle\EntityConfigBundle\Metadata\Attribute',
        'Oro\Bundle\SecurityBundle\Annotation' => 'Oro\Bundle\SecurityBundle\Attribute',
        'Oro\Bundle\NavigationBundle\Annotation' => 'Oro\Bundle\NavigationBundle\Attribute',
        'Oro\Bundle\LayoutBundle\Annotation' => 'Oro\Bundle\LayoutBundle\Attribute',
        'Oro\Bundle\EntityExtendBundle\Annotation\ORM' => 'Oro\Bundle\EntityExtendBundle\Attribute\ORM',
        'Oro\Bundle\HelpBundle\Annotation' => 'Oro\Bundle\HelpBundle\Attribute',
    ]);
};
