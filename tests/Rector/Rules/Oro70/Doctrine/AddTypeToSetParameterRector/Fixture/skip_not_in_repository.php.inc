<?php

namespace App\Service;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;

class SomeService
{
    public function applyFilter(QueryBuilder $qb, \App\Entity\Product $product): void
    {
        $qb->setParameter('product', $product);
    }
}
