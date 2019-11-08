<?php

namespace App\Repository;

use App\Entity\Bitboard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Bitboard|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bitboard|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bitboard[]    findAll()
 * @method Bitboard[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BitboardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bitboard::class);
    }

    // /**
    //  * @return Bitboard[] Returns an array of Bitboard objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Bitboard
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
