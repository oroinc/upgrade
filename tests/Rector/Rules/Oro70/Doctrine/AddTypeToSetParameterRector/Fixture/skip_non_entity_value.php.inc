<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ProductRepository extends EntityRepository
{
    public function applyFilter(QueryBuilder $qb, \App\Dto\ProductDto $dto): void
    {
        $qb->setParameter('dto', $dto);
    }
}
