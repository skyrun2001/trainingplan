<?php

namespace App\Tests\Integration\Repository;

use App\Entity\ExerciseLog;
use App\Entity\User;
use App\Entity\WorkoutSession;
use App\Repository\ExerciseLogRepository;
use App\Tests\Integration\IntegrationTestCase;

class ExerciseLogRepositoryTest extends IntegrationTestCase
{
    private ExerciseLogRepository $repo;
    private User                  $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(ExerciseLogRepository::class);

        $this->user = new User();
        $this->user->setUsername('athlete');
        $this->user->setPassword('hash');
        $this->persist($this->user);
    }

    private function makeSessionWithLog(
        string $dateStr,
        string $exercise,
        float $weight,
        int $reps,
        string $type = 'push'
    ): ExerciseLog {
        $session = new WorkoutSession();
        $session->setDate(new \DateTime($dateStr));
        $session->setType($type);
        $session->setUser($this->user);

        $log = new ExerciseLog();
        $log->setExerciseName($exercise);
        $log->setWeightKg((string) $weight);
        $log->setReps($reps);
        $log->setSetNumber(1);
        $session->addExerciseLog($log);

        $this->persist($session);
        return $log;
    }

    // ── findAllExerciseNamesForUser ────────────────────────────────────────────

    public function testFindAllExerciseNamesReturnsDistinctNamesSorted(): void
    {
        $this->makeSessionWithLog('today',     'Squat',      100, 5);
        $this->makeSessionWithLog('yesterday', 'Bench Press', 80, 8);
        $this->makeSessionWithLog('-2 days',   'Squat',       95, 6);

        $names = $this->repo->findAllExerciseNamesForUser($this->user);

        $this->assertSame(['Bench Press', 'Squat'], $names);
    }

    public function testFindAllExerciseNamesReturnsEmptyForNewUser(): void
    {
        $this->assertSame([], $this->repo->findAllExerciseNamesForUser($this->user));
    }

    public function testFindAllExerciseNamesDoesNotReturnOtherUsersExercises(): void
    {
        $other = new User();
        $other->setUsername('other');
        $other->setPassword('hash');
        $this->persist($other);

        $session = new WorkoutSession();
        $session->setDate(new \DateTime('today'));
        $session->setType('push');
        $session->setUser($other);

        $log = new ExerciseLog();
        $log->setExerciseName('Deadlift');
        $log->setSetNumber(1);
        $session->addExerciseLog($log);
        $this->persist($session);

        $this->assertSame([], $this->repo->findAllExerciseNamesForUser($this->user));
    }

    // ── findMaxWeightPerExerciseForUser ────────────────────────────────────────

    public function testFindMaxWeightPerExerciseReturnsMaxValues(): void
    {
        $this->makeSessionWithLog('today',     'Squat', 120, 5);
        $this->makeSessionWithLog('yesterday', 'Squat', 110, 5);
        $this->makeSessionWithLog('today',     'Bench Press', 90, 8);

        $results = $this->repo->findMaxWeightPerExerciseForUser($this->user);

        $map = array_column($results, null, 'exerciseName');

        $this->assertArrayHasKey('Squat',       $map);
        $this->assertArrayHasKey('Bench Press', $map);
        $this->assertEquals(120, $map['Squat']['maxWeight']);
        $this->assertEquals(90,  $map['Bench Press']['maxWeight']);
    }

    // ── findProgressionChartDataForUser ───────────────────────────────────────

    public function testFindProgressionChartDataReturnsDateOrderedRows(): void
    {
        $this->makeSessionWithLog('-5 days', 'Squat', 100, 5);
        $this->makeSessionWithLog('-3 days', 'Squat', 110, 5);
        $this->makeSessionWithLog('today',   'Squat', 120, 4);

        $data = $this->repo->findProgressionChartDataForUser('Squat', $this->user);

        $this->assertCount(3, $data);
        $this->assertSame(100.0, $data[0]['maxWeight']);
        $this->assertSame(110.0, $data[1]['maxWeight']);
        $this->assertSame(120.0, $data[2]['maxWeight']);
    }

    public function testFindProgressionChartDataReturnsEmptyForUnknownExercise(): void
    {
        $data = $this->repo->findProgressionChartDataForUser('Unknown', $this->user);
        $this->assertSame([], $data);
    }

    public function testFindProgressionChartDataRowHasCorrectShape(): void
    {
        $this->makeSessionWithLog('today', 'Deadlift', 150, 3);

        $data = $this->repo->findProgressionChartDataForUser('Deadlift', $this->user);

        $this->assertCount(1, $data);
        $this->assertArrayHasKey('date',      $data[0]);
        $this->assertArrayHasKey('maxWeight', $data[0]);
        $this->assertArrayHasKey('maxReps',   $data[0]);
        $this->assertIsString($data[0]['date']);
        $this->assertIsFloat($data[0]['maxWeight']);
        $this->assertIsInt($data[0]['maxReps']);
    }
}
