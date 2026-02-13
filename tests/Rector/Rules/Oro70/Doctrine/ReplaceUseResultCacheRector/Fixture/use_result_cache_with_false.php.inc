<?php

namespace App\Repository;

use Doctrine\ORM\AbstractQuery;

class ProductRepository
{
    public function findProducts(AbstractQuery $query)
    {
        $query->useResultCache(false, 3600, 'cache_key');
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
        $query->disableResultCache();
    }
}
