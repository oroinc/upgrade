<?php

namespace App\Entity;

/**
 * Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod = 1/29/"Lorem Ipsum Dolor Sit".
 * Sed ut perspiciatis unde omnis iste natus error sit voluptatem using "lorem_ipsum" file.
 *
 * @ORM\Entity()
 * @Config(
 *     defaultValues={
 *          "ownership"={"owner_type"="ORGANIZATION","owner_field_name"="organization"}
 *     }
 * )
 */
class SimpleClass
{
    /**
     * Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod = 1/29/"Lorem Ipsum Dolor Sit".
     * This field contains "quoted" values that should be replaced.
     *
     * @ORM\Column(type="string")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    private string $categoryCode;
}

-----
<?php

namespace App\Entity;

/**
 * Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod = 1/29/'Lorem Ipsum Dolor Sit'.
 * Sed ut perspiciatis unde omnis iste natus error sit voluptatem using 'lorem_ipsum' file.
 *
 * @ORM\Entity()
 * @Config(
 *     defaultValues={
 *          "ownership"={"owner_type"="ORGANIZATION","owner_field_name"="organization"}
 *     }
 * )
 */
class SimpleClass
{
    /**
     * Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod = 1/29/'Lorem Ipsum Dolor Sit'.
     * This field contains 'quoted' values that should be replaced.
     *
     * @ORM\Column(type="string")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    private string $categoryCode;
}
