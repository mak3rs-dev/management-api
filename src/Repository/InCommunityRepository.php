<?php

namespace App\Repository;

use App\Entity\InCommunity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method InCommunity|null find($id, $lockMode = null, $lockVersion = null)
 * @method InCommunity|null findOneBy(array $criteria, array $orderBy = null)
 * @method InCommunity[]    findAll()
 * @method InCommunity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InCommunityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InCommunity::class);
    }

    // /**
    //  * @return InCommunity[] Returns an array of InCommunity objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InCommunity
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
