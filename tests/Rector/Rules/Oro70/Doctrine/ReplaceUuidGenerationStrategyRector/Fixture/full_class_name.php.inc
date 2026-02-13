<?php

class EntityWithFullClassName
{
    #[\Doctrine\ORM\Mapping\Id]
    #[\Doctrine\ORM\Mapping\GeneratedValue(strategy: 'UUID')]
    private $id;
}
-----
<?php

class EntityWithFullClassName
{
    #[\Doctrine\ORM\Mapping\Id]
    #[\Doctrine\ORM\Mapping\GeneratedValue(strategy: 'CUSTOM')]
    #[\Doctrine\ORM\Mapping\CustomIdGenerator(class: \Oro\Component\DoctrineUtils\ORM\Id\UuidGenerator::class)]
    private $id;
}
