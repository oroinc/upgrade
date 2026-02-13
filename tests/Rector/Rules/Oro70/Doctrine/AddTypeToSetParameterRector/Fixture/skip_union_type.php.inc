<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ProductRepository extends EntityRepository
{
    public function applyFilter(QueryBuilder $qb, $entity): void
    {
        /** @var \App\Entity\Product|\App\Entity\Category $entity */
        $qb->setParameter('entity', $entity);
    }
}
