<?php

namespace App\Repository;

use App\Entity\ExerciseLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExerciseLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseLog::class);
    }

    public function findProgressionForExercise(string $exerciseName, int $limit = 30): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.session', 's')
            ->select('e.setNumber, e.reps, e.weightKg, s.date, s.type')
            ->where('e.exerciseName = :name')
            ->setParameter('name', $exerciseName)
            ->orderBy('s.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function findAllExerciseNames(): array
    {
        $result = $this->createQueryBuilder('e')
            ->select('DISTINCT e.exerciseName')
            ->orderBy('e.exerciseName', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'exerciseName');
    }

    public function findMaxWeightPerExercise(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.exerciseName, MAX(e.weightKg) as maxWeight, MAX(e.reps) as maxReps')
            ->groupBy('e.exerciseName')
            ->orderBy('e.exerciseName', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function findProgressionChartData(string $exerciseName): array
    {
        $rows = $this->createQueryBuilder('e')
            ->join('e.session', 's')
            ->select('s.date, MAX(e.weightKg) as maxWeight, MAX(e.reps) as maxReps')
            ->where('e.exerciseName = :name')
            ->setParameter('name', $exerciseName)
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
}
