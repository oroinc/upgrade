<?php

namespace App\Repository;

use Doctrine\ORM\AbstractQuery;

class ProductRepository
{
    public function findProducts(AbstractQuery $query)
    {
        $query->useResultCache(true);
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
        $query->enableResultCache();
    }
}
