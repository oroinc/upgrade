<?php

use Oro\Rector\RenameValueNormalizerUtilMethodIfTrowsExceptionRector;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    // - The parameter throwException was removed from the method convertToEntityType of Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil.
    // Use the tryConvertToEntityType method when an entity type might not exist.
    // - The parameter throwException was removed from the method convertToEntityClass of Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil.
    // Use the tryConvertToEntityClass method when an entity class might not exist.
    $rectorConfig->rule(RenameValueNormalizerUtilMethodIfTrowsExceptionRector::class);
};
