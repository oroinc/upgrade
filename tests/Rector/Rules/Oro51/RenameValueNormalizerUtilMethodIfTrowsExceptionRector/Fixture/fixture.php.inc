<?php

\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType, true);
\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType, false);

\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType);
\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType, true);
\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType, false);

?>
-----
<?php

\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityType);
\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::tryConvertToEntityType($valueNormalizer, $entityType);

\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType);
\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType);
\Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil::tryConvertToEntityClass($valueNormalizer, $entityType);

?>
