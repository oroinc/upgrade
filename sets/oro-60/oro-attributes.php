<?php

use Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;

return static function (RectorConfig $rectorConfig): void {
    // Transform custom annotations to attributes
    // Default case
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        // Config Annotations
        new AnnotationToAttribute(tag: 'Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config'),
        new AnnotationToAttribute(tag: 'Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField'),
        // Acl Annotations
        new AnnotationToAttribute(tag: 'Oro\Bundle\SecurityBundle\Attribute\Acl'),
        new AnnotationToAttribute(tag: 'Oro\Bundle\SecurityBundle\Attribute\AclAncestor'),
        new AnnotationToAttribute(tag: 'Oro\Bundle\SecurityBundle\Attribute\CsrfProtection'),
        // Title Template Annotations
        new AnnotationToAttribute(tag: 'Oro\Bundle\NavigationBundle\Attribute\TitleTemplate'),
        // Layout Annotations
        new AnnotationToAttribute(tag: 'Oro\Bundle\LayoutBundle\Attribute\Layout'),
        // Discriminator Value Annotations
        new AnnotationToAttribute(tag: 'Oro\Bundle\EntityExtendBundle\Attribute\ORM\DiscriminatorValue'),
        // Help  Annotations
        new AnnotationToAttribute(tag: 'Oro\Bundle\HelpBundle\Attribute\Help'),
        // Demo Data Annotations
        new AnnotationToAttribute(tag: 'Oro\Bundle\DemoDataCRMProBundle\Fake\NamePrefix'),
        new AnnotationToAttribute(tag: 'Oro\Bundle\DemoDataCRMProBundle\Fake\RouteResource'),
    ]);

    // Process cases when annotations are defined via fully qualified name
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        // Config Annotations
        new AnnotationToAttribute(
            tag: 'Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config',
            attributeClass: 'Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config'
        ),
        new AnnotationToAttribute(
            tag: 'Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField',
            attributeClass: 'Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField'
        ),
        // Acl Annotations
        new AnnotationToAttribute(
            tag: 'Oro\Bundle\SecurityBundle\Annotation\Acl',
            attributeClass: 'Oro\Bundle\SecurityBundle\Attribute\Acl'
        ),
        new AnnotationToAttribute(
            tag: 'Oro\Bundle\SecurityBundle\Annotation\AclAncestor',
            attributeClass: 'Oro\Bundle\SecurityBundle\Attribute\AclAncestor'
        ),
        new AnnotationToAttribute(
            tag: 'Oro\Bundle\SecurityBundle\Annotation\CsrfProtection',
            attributeClass: 'Oro\Bundle\SecurityBundle\Attribute\CsrfProtection'
        ),
        // Title Template Annotations
        new AnnotationToAttribute(
            tag: 'Oro\Bundle\NavigationBundle\Annotation\TitleTemplate',
            attributeClass: 'Oro\Bundle\NavigationBundle\Attribute\TitleTemplate'
        ),
        // Layout Annotations
        new AnnotationToAttribute(
            tag: 'Oro\Bundle\LayoutBundle\Annotation\Layout',
            attributeClass: 'Oro\Bundle\LayoutBundle\Attribute\Layout'
        ),
        // Discriminator Value Annotations
        new AnnotationToAttribute(
            tag: 'Oro\Bundle\EntityExtendBundle\Annotation\ORM\DiscriminatorValue',
            attributeClass: 'Oro\Bundle\EntityExtendBundle\Attribute\ORM\DiscriminatorValue'
        ),
        // Help  Annotations
        new AnnotationToAttribute(
            tag: 'Oro\Bundle\HelpBundle\Annotation\Help',
            attributeClass: 'Oro\Bundle\HelpBundle\Attribute\Help'
        ),
    ]);

    // Process cases when annotation classes are not imported
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        // Config Annotations
        new AnnotationToAttribute(
            tag: 'Config',
            attributeClass: 'Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config'
        ),
        new AnnotationToAttribute(
            tag: 'ConfigField',
            attributeClass: 'Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField'
        ),
        // Acl Annotations
        new AnnotationToAttribute(
            tag: 'Acl',
            attributeClass: 'Oro\Bundle\SecurityBundle\Attribute\Acl'
        ),
        new AnnotationToAttribute(
            tag: 'AclAncestor',
            attributeClass: 'Oro\Bundle\SecurityBundle\Attribute\AclAncestor'
        ),
        new AnnotationToAttribute(
            tag: 'CsrfProtection',
            attributeClass: 'Oro\Bundle\SecurityBundle\Attribute\CsrfProtection'
        ),
        // Title Template Annotations
        new AnnotationToAttribute(
            tag: 'TitleTemplate',
            attributeClass: 'Oro\Bundle\NavigationBundle\Attribute\TitleTemplate'
        ),
        // Layout Annotations
        new AnnotationToAttribute(
            tag: 'Layout',
            attributeClass: 'Oro\Bundle\LayoutBundle\Attribute\Layout'
        ),
        // Discriminator Value Annotations
        new AnnotationToAttribute(
            tag: 'DiscriminatorValue',
            attributeClass: 'Oro\Bundle\EntityExtendBundle\Attribute\ORM\DiscriminatorValue'
        ),
        // Help  Annotations
        new AnnotationToAttribute(
            tag: 'Help',
            attributeClass: 'Oro\Bundle\HelpBundle\Attribute\Help'
        ),
        // Demo Data Annotations
        new AnnotationToAttribute(
            tag: 'NamePrefix',
            attributeClass: 'Oro\Bundle\DemoDataCRMProBundle\Fake\NamePrefix'
        ),
        new AnnotationToAttribute(
            tag: 'RouteResource',
            attributeClass: 'Oro\Bundle\DemoDataCRMProBundle\Fake\RouteResource'
        ),
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
