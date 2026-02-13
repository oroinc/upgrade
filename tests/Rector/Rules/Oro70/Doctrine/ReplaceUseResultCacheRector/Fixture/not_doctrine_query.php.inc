<?php

namespace App\Repository;

class ProductRepository
{
    public function findProducts($query)
    {
        $query->useResultCache(true, 3600, 'cache_key');
    }
}
-----
<?php

namespace App\Repository;

class ProductRepository
{
    public function findProducts($query)
    {
        $query->useResultCache(true, 3600, 'cache_key');
    }
}
