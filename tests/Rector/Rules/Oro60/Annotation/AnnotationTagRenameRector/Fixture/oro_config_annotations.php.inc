<?php

namespace App\Entity;

/**
 * @Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config(
 *     routeName="oro_test_index",
 *     defaultValues={
 *         "entity"={"icon"="fa-test"}
 *     }
 * )
 */
class TestConfigEntity
{
    /**
     * @Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField(
     *     defaultValues={
 *         "dataaudit"={"auditable"=true}
 *     }
     * )
     */
    private $auditableField;
}

-----
<?php

namespace App\Entity;

/**
 * @Config(
 *     routeName="oro_test_index",
 *     defaultValues={
 *         "entity"={"icon"="fa-test"}
 *     }
 * )
 */
class TestConfigEntity
{
    /**
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={"auditable"=true}
     *     }
     * )
     */
    private $auditableField;
}
