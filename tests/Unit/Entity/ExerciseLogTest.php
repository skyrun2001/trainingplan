<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ExerciseLog;
use App\Entity\WorkoutSession;
use PHPUnit\Framework\TestCase;

class ExerciseLogTest extends TestCase
{
    private ExerciseLog $log;

    protected function setUp(): void
    {
        $this->log = new ExerciseLog();
    }

    public function testDefaultValues(): void
    {
        $this->assertNull($this->log->getId());
        $this->assertNull($this->log->getSession());
        $this->assertNull($this->log->getExerciseName());
        $this->assertSame(1, $this->log->getSetNumber());
        $this->assertNull($this->log->getReps());
        $this->assertNull($this->log->getWeightKg());
        $this->assertNull($this->log->getRpe());
    }

    public function testSetExerciseName(): void
    {
        $this->log->setExerciseName('Squat');
        $this->assertSame('Squat', $this->log->getExerciseName());
    }

    public function testSetSetNumber(): void
    {
        $this->log->setSetNumber(3);
        $this->assertSame(3, $this->log->getSetNumber());
    }

    public function testSetReps(): void
    {
        $this->log->setReps(10);
        $this->assertSame(10, $this->log->getReps());
    }

    public function testSetRepsToNull(): void
    {
        $this->log->setReps(5);
        $this->log->setReps(null);
        $this->assertNull($this->log->getReps());
    }

    public function testSetWeightKg(): void
    {
        $this->log->setWeightKg('80.50');
        $this->assertSame('80.50', $this->log->getWeightKg());
    }

    public function testSetRpe(): void
    {
        $this->log->setRpe(8);
        $this->assertSame(8, $this->log->getRpe());
    }

    public function testSetSession(): void
    {
        $session = new WorkoutSession();
        $this->log->setSession($session);
        $this->assertSame($session, $this->log->getSession());
    }

    public function testSettersReturnStaticForChaining(): void
    {
        $session = new WorkoutSession();
        $this->assertSame($this->log, $this->log->setSession($session));
        $this->assertSame($this->log, $this->log->setExerciseName('Deadlift'));
        $this->assertSame($this->log, $this->log->setSetNumber(1));
        $this->assertSame($this->log, $this->log->setReps(5));
        $this->assertSame($this->log, $this->log->setWeightKg('100.0'));
        $this->assertSame($this->log, $this->log->setRpe(9));
    }
}
