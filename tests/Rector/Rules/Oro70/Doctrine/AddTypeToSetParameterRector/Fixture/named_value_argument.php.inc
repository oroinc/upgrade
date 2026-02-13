<?php

namespace App\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ProductRepository extends EntityRepository
{
    public function applyFilter(QueryBuilder $qb, \App\Entity\Product $product): void
    {
        $qb->setParameter(value: $product, key: 'product');
    }
}
-----
<?php

namespace App\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ProductRepository extends EntityRepository
{
    public function applyFilter(QueryBuilder $qb, \App\Entity\Product $product): void
    {
        $qb->setParameter(value: $product->getId(), key: 'product', type: \Doctrine\DBAL\Types\Types::INTEGER);
    }
}
