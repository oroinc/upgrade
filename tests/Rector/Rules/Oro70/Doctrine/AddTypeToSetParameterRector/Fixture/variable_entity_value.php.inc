<?php

namespace App\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ProductRepository extends EntityRepository
{
    public function applyFilter(QueryBuilder $qb, \App\Entity\Product $product): void
    {
        $qb->setParameter('product', $product);
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
        $qb->setParameter('product', $product->getId(), \Doctrine\DBAL\Types\Types::INTEGER);
    }
}
