<?php

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\RenameAnnotationTag;
use Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\AnnotationTagRenameRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\MinimizeAnnotationRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\SanitiseDocBlockRector;
use Rector\Config\RectorConfig;

/**
 * This rule-set needed to preprocess annotations to avoid
 * mistakes while annotations are being transformed to attributes on the next steps
 */
return static function (RectorConfig $rectorConfig): void {
    // Replace defined chars in the DocBlock comments
    $rectorConfig->ruleWithConfiguration(
        SanitiseDocBlockRector::class,
        [
            //oldChar => newChar,
            '"' => "'",
        ]
    );

    // Rename tags to avoid transforming results such as #[Doctrine\ORM\Mapping\Id]
    $rectorConfig->ruleWithConfiguration(
        AnnotationTagRenameRector::class,
        [
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\Entity',
                newTag: 'ORM\Entity',
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\Column',
                newTag: 'ORM\Column'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\UniqueConstraint',
                newTag: 'ORM\UniqueConstraint'
            ),
            // id
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\Id',
                newTag: 'ORM\Id'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\GeneratedValue',
                newTag: 'ORM\GeneratedValue'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\SequenceGenerator',
                newTag: 'ORM\SequenceGenerator'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\Index',
                newTag: 'ORM\Index'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\CustomIdGenerator',
                newTag: 'ORM\CustomIdGenerator',
            ),
            // relations
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\OneToOne',
                newTag: 'ORM\OneToOne',
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\OneToMany',
                newTag: 'ORM\OneToMany',
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\ManyToMany',
                newTag: 'ORM\ManyToMany',
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\JoinTable',
                newTag: 'ORM\JoinTable'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\ManyToOne',
                newTag: 'ORM\ManyToOne',
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\OrderBy',
                newTag: 'ORM\OrderBy'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\JoinColumn',
                newTag: 'ORM\JoinColumn'
            ),
            // embed
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\Embeddable',
                newTag: 'ORM\Embeddable'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\Embedded',
                newTag: 'ORM\Embedded',
            ),
            // inheritance
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\MappedSuperclass',
                newTag: 'ORM\MappedSuperclass',
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\InheritanceType',
                newTag: 'ORM\InheritanceType'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\DiscriminatorColumn',
                newTag: 'ORM\DiscriminatorColumn'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\DiscriminatorMap',
                newTag: 'ORM\DiscriminatorMap'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\Version',
                newTag: 'ORM\Version'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\ChangeTrackingPolicy',
                newTag: 'ORM\ChangeTrackingPolicy'
            ),
            // events
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\HasLifecycleCallbacks',
                newTag: 'ORM\HasLifecycleCallbacks'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\PostLoad',
                newTag: 'ORM\PostLoad'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\PostPersist',
                newTag: 'ORM\PostPersist'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\PostRemove',
                newTag: 'ORM\PostRemove'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\PostUpdate',
                newTag: 'ORM\PostUpdate'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\PreFlush',
                newTag: 'ORM\PreFlush'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\PrePersist',
                newTag: 'ORM\PrePersist'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\PreRemove',
                newTag: 'ORM\PreRemove'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\PreUpdate',
                newTag: 'ORM\PreUpdate'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\Cache',
                newTag: 'ORM\Cache'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\EntityListeners',
                newTag: 'ORM\EntityListeners'
            ),
            // Overrides
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\AssociationOverrides',
                newTag: 'ORM\AssociationOverrides'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\AssociationOverride',
                newTag: 'ORM\AssociationOverride'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\AttributeOverrides',
                newTag: 'ORM\AttributeOverrides'
            ),
            new RenameAnnotationTag(
                oldTag: 'Doctrine\ORM\Mapping\AttributeOverride',
                newTag: 'ORM\AttributeOverride'
            ),
        ]
    );

    // Transform multi-lined annotations to single-lined excluding extra chars.
    $rectorConfig->ruleWithConfiguration(
        MinimizeAnnotationRector::class,
        [
            // Regular Cases
            'Config',
            'ConfigField',
            'Acl',
            'AclAncestor',
            'CsrfProtection',
            'TitleTemplate',
            'Layout',
            'DiscriminatorValue',
            'Help',
            'NamePrefix',
            'RouteResource',
            // Fully Qualified Names Cases
            'Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config',
            'Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField',
            'Oro\Bundle\SecurityBundle\Annotation\Acl',
            'Oro\Bundle\SecurityBundle\Annotation\AclAncestor',
            'Oro\Bundle\SecurityBundle\Annotation\CsrfProtection',
            'Oro\Bundle\NavigationBundle\Annotation\TitleTemplate',
            'Oro\Bundle\LayoutBundle\Annotation\Layout',
            'Oro\Bundle\EntityExtendBundle\Annotation\ORM\DiscriminatorValue',
            'Oro\Bundle\HelpBundle\Annotation\Help',
            'Oro\Bundle\DemoDataCRMProBundle\Fake\NamePrefix',
            'Oro\Bundle\DemoDataCRMProBundle\Fake\RouteResource',
         ]
    );
};
