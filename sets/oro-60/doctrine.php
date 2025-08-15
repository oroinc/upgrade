<?php
/**
 * Modified copy of doctrine set, Rector v2.1.2
 * Defined more strict configuration to cover some edge-cases for doctrine annotations
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

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;

return static function (RectorConfig $rectorConfig): void {
    // NestedAnnotationToAttributeRector config kept in the default state

    /**
     * Process tags like ORM\DoctrineAnnotation
     * {@see preprocess-annotations.php}
     */
    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
        // class
        new AnnotationToAttribute(
            tag: 'ORM\Entity',
            attributeClass: 'Doctrine\ORM\Mapping\Entity',
            classReferenceFields: ['repositoryClass']
        ),
        new AnnotationToAttribute(
            tag: 'ORM\Column',
            attributeClass: 'Doctrine\ORM\Mapping\Column'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\UniqueConstraint',
            attributeClass: 'Doctrine\ORM\Mapping\UniqueConstraint'
        ),
        // id
        new AnnotationToAttribute(
            tag: 'ORM\Id',
            attributeClass: 'Doctrine\ORM\Mapping\Id'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\GeneratedValue',
            attributeClass: 'Doctrine\ORM\Mapping\GeneratedValue'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\SequenceGenerator',
            attributeClass: 'Doctrine\ORM\Mapping\SequenceGenerator'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\Index',
            attributeClass: 'Doctrine\ORM\Mapping\Index'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\CustomIdGenerator',
            attributeClass: 'Doctrine\ORM\Mapping\CustomIdGenerator',
            classReferenceFields: ['class']
        ),
        // relations
        new AnnotationToAttribute(
            tag: 'ORM\OneToOne',
            attributeClass: 'Doctrine\ORM\Mapping\OneToOne',
            classReferenceFields: ['targetEntity']
        ),
        new AnnotationToAttribute(
            tag: 'ORM\OneToMany',
            attributeClass: 'Doctrine\ORM\Mapping\OneToMany',
            classReferenceFields: ['targetEntity']
        ),
        new AnnotationToAttribute(
            tag: 'ORM\ManyToMany',
            attributeClass: 'Doctrine\ORM\Mapping\ManyToMany',
            classReferenceFields: ['targetEntity']
        ),
        new AnnotationToAttribute(
            tag: 'ORM\JoinTable',
            attributeClass: 'Doctrine\ORM\Mapping\JoinTable'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\ManyToOne',
            attributeClass: 'Doctrine\ORM\Mapping\ManyToOne',
            classReferenceFields: ['targetEntity']
        ),
        new AnnotationToAttribute(
            tag: 'ORM\OrderBy',
            attributeClass: 'Doctrine\ORM\Mapping\OrderBy'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\JoinColumn',
            attributeClass: 'Doctrine\ORM\Mapping\JoinColumn'
        ),
        // embed
        new AnnotationToAttribute(
            tag: 'ORM\Embeddable',
            attributeClass: 'Doctrine\ORM\Mapping\Embeddable'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\Embedded',
            attributeClass: 'Doctrine\ORM\Mapping\Embedded',
            classReferenceFields: ['class']
        ),
        // inheritance
        new AnnotationToAttribute(
            tag: 'ORM\MappedSuperclass',
            attributeClass: 'Doctrine\ORM\Mapping\MappedSuperclass',
            classReferenceFields: ['repositoryClass']
        ),
        new AnnotationToAttribute(
            tag: 'ORM\InheritanceType',
            attributeClass: 'Doctrine\ORM\Mapping\InheritanceType'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\DiscriminatorColumn',
            attributeClass: 'Doctrine\ORM\Mapping\DiscriminatorColumn'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\DiscriminatorMap',
            attributeClass: 'Doctrine\ORM\Mapping\DiscriminatorMap'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\Version',
            attributeClass: 'Doctrine\ORM\Mapping\Version'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\ChangeTrackingPolicy',
            attributeClass: 'Doctrine\ORM\Mapping\ChangeTrackingPolicy'
        ),
        // events
        new AnnotationToAttribute(
            tag: 'ORM\HasLifecycleCallbacks',
            attributeClass: 'Doctrine\ORM\Mapping\HasLifecycleCallbacks'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\PostLoad',
            attributeClass: 'Doctrine\ORM\Mapping\PostLoad'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\PostPersist',
            attributeClass: 'Doctrine\ORM\Mapping\PostPersist'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\PostRemove',
            attributeClass: 'Doctrine\ORM\Mapping\PostRemove'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\PostUpdate',
            attributeClass: 'Doctrine\ORM\Mapping\PostUpdate'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\PreFlush',
            attributeClass: 'Doctrine\ORM\Mapping\PreFlush'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\PrePersist',
            attributeClass: 'Doctrine\ORM\Mapping\PrePersist'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\PreRemove',
            attributeClass: 'Doctrine\ORM\Mapping\PreRemove'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\PreUpdate',
            attributeClass: 'Doctrine\ORM\Mapping\PreUpdate'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\Cache',
            attributeClass: 'Doctrine\ORM\Mapping\Cache'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\EntityListeners',
            attributeClass: 'Doctrine\ORM\Mapping\EntityListeners'
        ),
        // Overrides
        new AnnotationToAttribute(
            tag: 'ORM\AssociationOverrides',
            attributeClass: 'Doctrine\ORM\Mapping\AssociationOverrides'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\AssociationOverride',
            attributeClass: 'Doctrine\ORM\Mapping\AssociationOverride'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\AttributeOverrides',
            attributeClass: 'Doctrine\ORM\Mapping\AttributeOverrides'
        ),
        new AnnotationToAttribute(
            tag: 'ORM\AttributeOverride',
            attributeClass: 'Doctrine\ORM\Mapping\AttributeOverride'
        ),
    ]);
};
