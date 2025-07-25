<?php

use Oro\UpgradeToolkit\Rector\Rules\Namespace\RenameNamespaceRector;
use Oro\UpgradeToolkit\Rector\Rules\Oro70\Console\ReplaceGetDefaultNameWithAttributeNameValueRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
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
};
