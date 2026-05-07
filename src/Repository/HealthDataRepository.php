<?php

namespace App\Repository;

use App\Entity\HealthData;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HealthData>
 */
class HealthDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthData::class);
    }

    public function findByUserAndDate(User $user, \DateTimeInterface $date): ?HealthData
    {
        return $this->findOneBy(['user' => $user, 'date' => $date]);
    }

    public function findRecentForUser(User $user, int $days = 7): array
    {
        $since = (new \DateTime())->modify("-{$days} days");
        return $this->createQueryBuilder('h')
            ->where('h.user = :user')
            ->andWhere('h.date >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->orderBy('h.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
