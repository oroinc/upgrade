<?php

use Doctrine\ORM\Mapping as ORM;

class MultiplePropertiesEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'UUID')]
    private $id;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $secondId;

    #[ORM\Column]
    private $name;
}
-----
<?php

use Doctrine\ORM\Mapping as ORM;

class MultiplePropertiesEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[\Doctrine\ORM\Mapping\CustomIdGenerator(class: \Oro\Component\DoctrineUtils\ORM\Id\UuidGenerator::class)]
    private $id;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $secondId;

    #[ORM\Column]
    private $name;
}
