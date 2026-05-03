<?php

namespace App\Repository;

use App\Entity\TrainingPlan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TrainingPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingPlan::class);
    }

    public function findActiveForUser(User $user): ?TrainingPlan
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.isActive = true')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
