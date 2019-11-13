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

    public function getKing($color)
    {
        $qb = $this->createQueryBuilder('b');
        $qb->leftJoin('b.piece', 'piece');
        $qb->where("piece.code ='king' ");
        $qb->where("b.name = 'current_position' ");
        $qb->andWhere("b.color =:color ");
        $qb->setParameter(':color', $color);

        return $qb;
    }


}
