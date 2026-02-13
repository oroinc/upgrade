<?php

namespace App\Repository;

use Doctrine\ORM\AbstractQuery;

class ProductRepository
{
    public function findProducts(AbstractQuery $query)
    {
        $query->useResultCache();
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
        $query->useResultCache();
    }
}
