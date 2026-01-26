<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeTemplateArgumentRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(TemplateAttributeTemplateArgumentRector::class);
};
