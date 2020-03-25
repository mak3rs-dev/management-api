<?php

namespace App\Repository;

use App\Entity\CollectControl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method CollectControl|null find($id, $lockMode = null, $lockVersion = null)
 * @method CollectControl|null findOneBy(array $criteria, array $orderBy = null)
 * @method CollectControl[]    findAll()
 * @method CollectControl[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectControlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectControl::class);
    }

    // /**
    //  * @return CollectControl[] Returns an array of CollectControl objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CollectControl
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
