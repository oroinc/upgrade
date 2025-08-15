<?php

namespace App\Entity;

class EdgeCasesEntity
{
    /**
     * Test compact annotations without spaces
     *@Doctrine\ORM\Mapping\Column(type="string")
     */
    private $compactField;

    /**
     * Test multiple annotations on same line
     * @Doctrine\ORM\Mapping\Column(type="integer") @ConfigField
     */
    private $multiLineField;
}

-----
<?php

namespace App\Entity;

class EdgeCasesEntity
{
    /**
     * Test compact annotations without spaces
     *@ORM\Column(type="string")
     */
    private $compactField;

    /**
     * Test multiple annotations on same line
     * @ORM\Column(type="integer") @ConfigField
     */
    private $multiLineField;
}
