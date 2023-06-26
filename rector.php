<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src/',
        __DIR__ . '/tests/',
    ]);
    $rectorConfig->skip([
        '**/config/**',
        '**/Mocks/**',
        '**/Expected/**'
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::PSR_4,
        SetList::TYPE_DECLARATION,
    ]);
    $rectorConfig->rule(DeclareStrictTypesRector::class);
};
