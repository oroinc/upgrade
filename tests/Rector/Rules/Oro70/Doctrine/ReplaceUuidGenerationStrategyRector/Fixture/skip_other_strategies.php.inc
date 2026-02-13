<?php
use Doctrine\ORM\Mapping as ORM;
class EntityWithOtherStrategies
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id1;
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private $id2;
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $id3;
}
-----
<?php
use Doctrine\ORM\Mapping as ORM;
class EntityWithOtherStrategies
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id1;
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private $id2;
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private $id3;
}
