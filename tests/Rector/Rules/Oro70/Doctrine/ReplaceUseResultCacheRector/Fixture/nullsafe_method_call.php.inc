<?php

namespace App\Repository;

use Doctrine\ORM\AbstractQuery;

class ProductRepository
{
    public function findProducts(?AbstractQuery $query)
    {
        $query?->useResultCache(true, 3600, 'cache_key');
    }
}
-----
<?php

namespace App\Repository;

use Doctrine\ORM\AbstractQuery;

class ProductRepository
{
    public function findProducts(?AbstractQuery $query)
    {
        $query?->enableResultCache(3600, 'cache_key');
    }
}
