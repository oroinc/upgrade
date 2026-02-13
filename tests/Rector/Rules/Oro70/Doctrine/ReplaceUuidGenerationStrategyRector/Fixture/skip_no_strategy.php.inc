<?php

use Doctrine\ORM\Mapping as ORM;

class EntityWithoutStrategyParam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private $id;
}
-----
<?php

use Doctrine\ORM\Mapping as ORM;

class EntityWithoutStrategyParam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private $id;
}
