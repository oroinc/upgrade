<?php

namespace App\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class OrderRepository extends EntityRepository
{
    public function applyFilter(QueryBuilder $qb, \App\Entity\Order $order): void
    {
        $qb->setParameter('product', $order->getProduct());
    }
}
-----
<?php

namespace App\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class OrderRepository extends EntityRepository
{
    public function applyFilter(QueryBuilder $qb, \App\Entity\Order $order): void
    {
        $qb->setParameter('product', $order->getProduct()?->getId(), \Doctrine\DBAL\Types\Types::INTEGER);
    }
}
