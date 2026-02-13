<?php

namespace App\Repository;

use Doctrine\ORM\AbstractQuery;

class ProductRepository
{
    public function findProducts(AbstractQuery $query)
    {
        $query->useResultCache(true, 7200);
    }
}
-----
<?php

namespace App\Repository;

use Doctrine\ORM\AbstractQuery;

class ProductRepository
{
    public function findProducts(AbstractQuery $query)
    {
        $query->enableResultCache(7200);
    }
}
