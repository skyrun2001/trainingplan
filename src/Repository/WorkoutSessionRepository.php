<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WorkoutSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkoutSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutSession::class);
    }

    public function findRecentForUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByDateRangeAndUser(\DateTimeInterface $from, \DateTimeInterface $to, User $user): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.date BETWEEN :from AND :to')
            ->andWhere('w.user = :user')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('user', $user)
            ->orderBy('w.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByTypeForUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.type, COUNT(w.id) as cnt')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->groupBy('w.type')
            ->getQuery()
            ->getArrayResult();
    }

    public function getCurrentStreakForUser(User $user): int
    {
        $sessions = $this->createQueryBuilder('w')
            ->select('w.date')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.date', 'DESC')
            ->getQuery()
            ->getArrayResult();

        if (empty($sessions)) return 0;

        $dates = array_unique(array_map(fn($s) => $s['date']->format('Y-m-d'), $sessions));
        rsort($dates);

        $today     = (new \DateTime())->format('Y-m-d');
        $yesterday = (new \DateTime('yesterday'))->format('Y-m-d');
        $check     = in_array($today, $dates) ? $today : (in_array($yesterday, $dates) ? $yesterday : null);

        if ($check === null) return 0;

        $streak  = 0;
        $current = new \DateTime($check);
        foreach ($dates as $d) {
            if ($d === $current->format('Y-m-d')) {
                $streak++;
                $current->modify('-1 day');
            } elseif ($d < $current->format('Y-m-d')) {
                break;
            }
        }

        return $streak;
    }
}
