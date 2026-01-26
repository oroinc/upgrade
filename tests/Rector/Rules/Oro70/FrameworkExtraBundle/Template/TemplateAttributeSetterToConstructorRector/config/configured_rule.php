<?php

declare(strict_types=1);

use Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\Template\TemplateAttributeSetterToConstructorRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(TemplateAttributeSetterToConstructorRector::class);
};
