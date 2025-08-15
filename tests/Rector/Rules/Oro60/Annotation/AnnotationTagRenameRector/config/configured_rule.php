<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Renaming\ValueObject\RenameAnnotationTag;
use Oro\UpgradeToolkit\Rector\Rules\Oro60\Annotation\AnnotationTagRenameRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(
        AnnotationTagRenameRector::class,
        [
            new RenameAnnotationTag(oldTag: 'Doctrine\ORM\Mapping\Column', newTag: 'ORM\Column'),
            new RenameAnnotationTag(oldTag: 'Doctrine\ORM\Mapping\Table', newTag: 'ORM\Table'),
            new RenameAnnotationTag(oldTag: 'Doctrine\ORM\Mapping\Entity', newTag: 'ORM\Entity'),
            new RenameAnnotationTag(oldTag: 'Doctrine\ORM\Mapping\JoinColumn', newTag: 'ORM\JoinColumn'),
            new RenameAnnotationTag(oldTag: 'Doctrine\ORM\Mapping\OneToMany', newTag: 'ORM\OneToMany'),
            new RenameAnnotationTag(oldTag: 'Doctrine\ORM\Mapping\ManyToOne', newTag: 'ORM\ManyToOne'),
            new RenameAnnotationTag(
                oldTag: 'Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config',
                newTag: 'Config'
            ),
            new RenameAnnotationTag(
                oldTag: 'Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField',
                newTag: 'ConfigField'
            ),
        ]
    );
};
