<?php

namespace App\Repository;

use App\Entity\StockControl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method StockControl|null find($id, $lockMode = null, $lockVersion = null)
 * @method StockControl|null findOneBy(array $criteria, array $orderBy = null)
 * @method StockControl[]    findAll()
 * @method StockControl[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockControlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockControl::class);
    }

    // /**
    //  * @return StockControl[] Returns an array of StockControl objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StockControl
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
