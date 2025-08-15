<?php

namespace App\Entity;

/**
 * Mixed annotations test case
 * @Doctrine\ORM\Mapping\Entity
 * @Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config(
 *     defaultValues={
 *         "security"={"type"="ACL", "group_name"=""}
 *     }
 * )
 * @Doctrine\ORM\Mapping\Table(name="mixed_annotations")
 */
class MixedAnnotationsEntity
{
    /**
     * ID field with multiple annotations
     * @Doctrine\ORM\Mapping\Column(type="integer")
     * @Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField(defaultValues={"importexport"={"identity"=true}})
     */
    private $id;

    /**
     * Some method with annotations
     * @param string $value
     * @return void
     */
    public function someMethod($value): void
    {
        // method implementation
    }
}

-----
<?php

namespace App\Entity;

/**
 * Mixed annotations test case
 * @ORM\Entity
 * @Config(
 *     defaultValues={
 *         "security"={"type"="ACL", "group_name"=""}
 *     }
 * )
 * @ORM\Table(name="mixed_annotations")
 */
class MixedAnnotationsEntity
{
    /**
     * ID field with multiple annotations
     * @ORM\Column(type="integer")
     * @ConfigField(defaultValues={"importexport"={"identity"=true}})
     */
    private $id;

    /**
     * Some method with annotations
     * @param string $value
     * @return void
     */
    public function someMethod($value): void
    {
        // method implementation
    }
}
