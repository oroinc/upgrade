<?php

use Oro\UpgradeToolkit\Rector\Rules\Oro70\FrameworkExtraBundle\ParamConverter\AddressValidationActionParamConverterAttributeToMapEntityAttributeRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(AddressValidationActionParamConverterAttributeToMapEntityAttributeRector::class);
};
