<?php

namespace App\Repository;

use App\Entity\Supplement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Supplement>
 */
class SupplementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Supplement::class);
    }

    /** @return Supplement[] */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Supplement[] supplements where days remaining <= warningDays */
    public function findLowStockForUser(User $user): array
    {
        return array_filter(
            $this->findAllForUser($user),
            fn(Supplement $s) => $s->isLow() || $s->isEmpty()
        );
    }
}
