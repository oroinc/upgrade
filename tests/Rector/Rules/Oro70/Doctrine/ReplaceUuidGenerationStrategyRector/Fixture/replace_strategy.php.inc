<?php

use Doctrine\ORM\Mapping as ORM;

class SomeEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'UUID')]
    private $id;
}
-----
<?php

use Doctrine\ORM\Mapping as ORM;

class SomeEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[\Doctrine\ORM\Mapping\CustomIdGenerator(class: \Oro\Component\DoctrineUtils\ORM\Id\UuidGenerator::class)]
    private $id;
}
