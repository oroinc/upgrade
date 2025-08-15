<?php

namespace App\Entity;

/**
 * @Doctrine\ORM\Mapping\Entity
 * @Doctrine\ORM\Mapping\Table(name="test_entity")
 */
class TestEntity
{
    /**
     * @Doctrine\ORM\Mapping\Column(type="integer")
     */
    private $id;

    /**
     * @Doctrine\ORM\Mapping\Column(type="string", length=255)
     */
    private $name;
}

-----
<?php

namespace App\Entity;

/**
 * @ORM\Entity
 * @ORM\Table(name="test_entity")
 */
class TestEntity
{
    /**
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;
}
