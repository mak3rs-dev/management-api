<?php

namespace App\Repository;

use App\Entity\StackControl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method StackControl|null find($id, $lockMode = null, $lockVersion = null)
 * @method StackControl|null findOneBy(array $criteria, array $orderBy = null)
 * @method StackControl[]    findAll()
 * @method StackControl[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StackControlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StackControl::class);
    }

    // /**
    //  * @return StackControl[] Returns an array of StackControl objects
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
    public function findOneBySomeField($value): ?StackControl
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
