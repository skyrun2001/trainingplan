<?php

namespace App\Repository;

use App\Entity\HealthMetric;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HealthMetricRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthMetric::class);
    }

    public function findOneByUserDateKey(User $user, \DateTimeInterface $date, string $key): ?HealthMetric
    {
        return $this->findOneBy(['user' => $user, 'date' => $date, 'metricKey' => $key]);
    }

    /** Returns flat map: ['steps' => 8432, 'weight_kg' => 78.5, ...] for today */
    public function findTodayForUser(User $user): array
    {
        $records = $this->findBy(['user' => $user, 'date' => new \DateTime('today')]);
        $result  = [];
        foreach ($records as $r) {
            $result[$r->getMetricKey()] = $r->getMetricValue();
        }
        return $result;
    }

    /**
     * Returns rows ordered oldest→newest, each row is a flat array
     * merged with 'date' key: [['date'=>'2026-05-01','steps'=>8000,...], ...]
     */
    public function findRecentGroupedForUser(User $user, int $days): array
    {
        $records = $this->createQueryBuilder('h')
            ->where('h.user = :user')
            ->andWhere('h.date >= :start')
            ->setParameter('user', $user)
            ->setParameter('start', new \DateTime("-{$days} days"))
            ->orderBy('h.date', 'ASC')
            ->addOrderBy('h.metricKey', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($records as $r) {
            $date = $r->getDate()->format('Y-m-d');
            if (!isset($grouped[$date])) {
                $grouped[$date] = ['date' => $date];
            }
            $grouped[$date][$r->getMetricKey()] = $r->getMetricValue();
        }
        return array_values($grouped);
    }

    /** All distinct metric keys the user has ever synced, alphabetically */
    public function findAllMetricKeysForUser(User $user): array
    {
        return array_column(
            $this->createQueryBuilder('h')
                ->select('DISTINCT h.metricKey AS metricKey')
                ->where('h.user = :user')
                ->setParameter('user', $user)
                ->orderBy('h.metricKey', 'ASC')
                ->getQuery()
                ->getArrayResult(),
            'metricKey'
        );
    }
}
