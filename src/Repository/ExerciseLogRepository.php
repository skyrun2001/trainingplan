<?php

namespace App\Repository;

use App\Entity\ExerciseLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExerciseLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseLog::class);
    }

    public function findProgressionChartDataForUser(string $exerciseName, User $user): array
    {
        $rows = $this->createQueryBuilder('e')
            ->join('e.session', 's')
            ->select('s.date, MAX(e.weightKg) as maxWeight, MAX(e.reps) as maxReps')
            ->where('e.exerciseName = :name')
            ->andWhere('s.user = :user')
            ->setParameter('name', $exerciseName)
            ->setParameter('user', $user)
            ->groupBy('s.date')
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(fn($r) => [
            'date'      => $r['date']->format('d.m.Y'),
            'maxWeight' => (float) $r['maxWeight'],
            'maxReps'   => (int) $r['maxReps'],
        ], $rows);
    }

    public function findAllExerciseNamesForUser(User $user): array
    {
        $result = $this->createQueryBuilder('e')
            ->join('e.session', 's')
            ->select('DISTINCT e.exerciseName')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.exerciseName', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'exerciseName');
    }

    public function findMaxWeightPerExerciseForUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.session', 's')
            ->select('e.exerciseName, MAX(e.weightKg) as maxWeight, MAX(e.reps) as maxReps')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->groupBy('e.exerciseName')
            ->orderBy('e.exerciseName', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
