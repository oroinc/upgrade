<?php

use Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\Console\ReplaceGetDefaultNameWithAttributeNameValueRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\Form\AddFormWidgetAndHtml5OptionsRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\Serializer\AddGetSupportedTypesMethodRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Renaming\ValueObject\RenameClassAndConstFetch;
use Rector\Symfony\Symfony61\Rector\Class_\CommandConfigureToAttributeRector;
use Rector\Symfony\Symfony61\Rector\Class_\CommandPropertyToAttributeRector;

return static function (RectorConfig $rectorConfig): void {
    // deprecation.INFO: User Deprecated: Since symfony/framework-bundle 6.4:
    // - The "annotations.cache_adapter" service is deprecated without replacement.
    // - The "annotations.reader" service is deprecated without replacement.
    // - The "annotations.cached_reader" service is deprecated without replacement.
    $rectorConfig->ruleWithConfiguration(RenameNamespaceRector::class, [
        'Symfony\Component\Routing\Annotation' => 'Symfony\Component\Routing\Attribute',
    ]);

    // deprecation.INFO: User Deprecated: Since symfony/console 6.1:
    // Relying on the static property "$defaultName" for setting a command name is deprecated.
    // Relying on the static property "$defaultDescription" for setting a command description is deprecated.
    $rectorConfig->rules([CommandConfigureToAttributeRector::class, CommandPropertyToAttributeRector::class]);
    $rectorConfig->rule(ReplaceGetDefaultNameWithAttributeNameValueRector::class);

    // deprecation.INFO: User Deprecated: Since symfony/validator 6.1:
    // The "Symfony\Component\Validator\Constraints\ExpressionLanguageSyntaxValidator" constraint is deprecated
    // since symfony 6.1, use "ExpressionSyntaxValidator" instead.
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        // @see https://github.com/symfony/symfony/pull/45623
        'Symfony\\Component\\Validator\\Constraints\\ExpressionLanguageSyntax'
        => 'Symfony\\Component\\Validator\\Constraints\\ExpressionSyntax',
        'Symfony\\Component\\Validator\\Constraints\\ExpressionLanguageSyntaxValidator'
        => 'Symfony\\Component\\Validator\\Constraints\\ExpressionSyntaxValidator',
    ]);

    // deprecation.INFO: User Deprecated: Since symfony/serializer 6.3:
    // The "Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer::hasCacheableSupportsMethod()" method is deprecated
    // implement "Oro\Bundle\CacheBundle\Serializer\Normalizer\GetSetMethodNormalizer::getSupportedTypes()" instead.
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename(
            'Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer',
            'hasCacheableSupportsMethod',
            'getSupportedTypes'
        ),
    ]);

    // Serializer
    // Rename Interface
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Symfony\\Component\\Serializer\\Normalizer\\ContextAwareDenormalizerInterface'
        => 'Symfony\\Component\\Serializer\\Normalizer\\DenormalizerInterface',
        'Symfony\\Component\\Serializer\\Normalizer\\ContextAwareNormalizerInterface'
        => 'Symfony\\Component\\Serializer\\Normalizer\\NormalizerInterface',
    ]);

    // Add getSupportedTypes method
    $rectorConfig->rule(AddGetSupportedTypesMethodRector::class);

    // Form
    // Add widget and html5 options
    $rectorConfig->rule(AddFormWidgetAndHtml5OptionsRector::class);

    // Symfony Security constants migration
    $rectorConfig->ruleWithConfiguration(RenameClassConstFetchRector::class, [
        new RenameClassAndConstFetch(
            oldClass: 'Symfony\\Component\\Security\\Core\\Security',
            oldConstant: 'LAST_USERNAME',
            newClass: 'Symfony\\Component\\Security\\Http\\SecurityRequestAttributes',
            newConstant: 'LAST_USERNAME'
        ),
        new RenameClassAndConstFetch(
            oldClass: 'Symfony\\Component\\Security\\Core\\Security',
            oldConstant: 'AUTHENTICATION_ERROR',
            newClass: 'Symfony\\Component\\Security\\Http\\SecurityRequestAttributes',
            newConstant: 'AUTHENTICATION_ERROR'
        ),
        new RenameClassAndConstFetch(
            oldClass: 'Symfony\\Component\\Security\\Core\\Security',
            oldConstant: 'ACCESS_DENIED_ERROR',
            newClass: 'Symfony\\Component\\Security\\Http\\SecurityRequestAttributes',
            newConstant: 'ACCESS_DENIED_ERROR'
        ),
    ]);
};
