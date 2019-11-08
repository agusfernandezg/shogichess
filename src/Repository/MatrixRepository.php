<?php

namespace App\Repository;

use App\Entity\Matrix;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Matrix|null find($id, $lockMode = null, $lockVersion = null)
 * @method Matrix|null findOneBy(array $criteria, array $orderBy = null)
 * @method Matrix[]    findAll()
 * @method Matrix[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MatrixRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Matrix::class);
    }

    // /**
    //  * @return Matrix[] Returns an array of Matrix objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Matrix
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
