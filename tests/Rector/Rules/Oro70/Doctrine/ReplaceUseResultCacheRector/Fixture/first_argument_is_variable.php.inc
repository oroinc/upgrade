<?php

namespace App\Repository;

use Doctrine\ORM\AbstractQuery;

class ProductRepository
{
    public function findProducts(AbstractQuery $query, bool $enableCache)
    {
        $query->useResultCache($enableCache, 3600, 'cache_key');
    }
}
-----
<?php

namespace App\Repository;

use Doctrine\ORM\AbstractQuery;

class ProductRepository
{
    public function findProducts(AbstractQuery $query, bool $enableCache)
    {
        $query->useResultCache($enableCache, 3600, 'cache_key');
    }
}
