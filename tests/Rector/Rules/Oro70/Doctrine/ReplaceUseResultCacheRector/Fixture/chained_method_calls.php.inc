<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;

class ProductRepository
{
    public function findProducts(EntityManagerInterface $em)
    {
        $qb = $em->createQueryBuilder();
        $qb->select('p')
            ->from('Product', 'p')
            ->where('p.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->useResultCache(true, 3600, 'products_cache');

        return $qb->getQuery()->getResult();
    }
}
-----
<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;

class ProductRepository
{
    public function findProducts(EntityManagerInterface $em)
    {
        $qb = $em->createQueryBuilder();
        $qb->select('p')
            ->from('Product', 'p')
            ->where('p.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->enableResultCache(3600, 'products_cache');

        return $qb->getQuery()->getResult();
    }
}
