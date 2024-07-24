<?php

use Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;

return static function (RectorConfig $rectorConfig): void {
    // Transform custom annotations to attributes
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        // Config Annotations
        new AnnotationToAttribute('Config'),
        new AnnotationToAttribute('ConfigField'),
        // Acl Annotations
        new AnnotationToAttribute('Acl'),
        new AnnotationToAttribute('AclAncestor'),
        new AnnotationToAttribute('CsrfProtection'),
        // Title Template Annotations
        new AnnotationToAttribute('TitleTemplate'),
        // Layout Annotations
        new AnnotationToAttribute('Layout'),
        // Discriminator Value Annotations
        new AnnotationToAttribute('DiscriminatorValue'),
        // Help  Annotations
        new AnnotationToAttribute('Help'),
        // Demo Data Annotations
        new AnnotationToAttribute('NamePrefix'),
        new AnnotationToAttribute('RouteResource'),
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
