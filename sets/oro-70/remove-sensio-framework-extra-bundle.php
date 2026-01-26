<?php

use Oro\UpgradeToolkit\Rector\Rules\MethodCall\OroMethodCallToPropertyFetchRector;
use Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\ParamConverter\AddressValidationActionParamConverterAttributeToMapEntityAttributeRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\ParamConverter\OroParamConverterAttributeToMapEntityAttributeRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeArrayToArgsRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeSetterToConstructorRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeTemplateArgumentRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Transform\Rector\MethodCall\MethodCallToPropertyFetchRector;
use Rector\Transform\ValueObject\MethodCallToPropertyFetch;

/**
 * Rule set to simplify the removal of the sensio/framework-extra-bundle
 */
return static function (RectorConfig $rectorConfig): void {
    // Template
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Sensio\Bundle\FrameworkExtraBundle\Configuration\Template'
        => 'Symfony\Bridge\Twig\Attribute\Template'
    ]);

    $rectorConfig->rule(TemplateAttributeSetterToConstructorRector::class);
    $rectorConfig->rule(TemplateAttributeArrayToArgsRector::class);
    $rectorConfig->rule(TemplateAttributeTemplateArgumentRector::class);

    $rectorConfig->ruleWithConfiguration(MethodCallToPropertyFetchRector::class, [
        new MethodCallToPropertyFetch(
            'Symfony\Bridge\Twig\Attribute\Template',
            'getTemplate',
            'template'
        )
    ]);

    $rectorConfig->ruleWithConfiguration(OroMethodCallToPropertyFetchRector::class, [
        new MethodCallToPropertyFetch(
            'Symfony\Bridge\Twig\Attribute\Template',
            'setTemplate',
            'template'
        )
    ]);

    $rectorConfig->ruleWithConfiguration(RenameNamespaceRector::class, [
        'Sensio\Bundle\FrameworkExtraBundle\Configuration\Template'
        => 'Symfony\Bridge\Twig\Attribute\Template',
    ]);

    // ParamConverter
    $rectorConfig->rule(AddressValidationActionParamConverterAttributeToMapEntityAttributeRector::class);
    $rectorConfig->rule(OroParamConverterAttributeToMapEntityAttributeRector::class);
};
