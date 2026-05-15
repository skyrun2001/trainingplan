<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ExerciseLog;
use App\Entity\WorkoutSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WorkoutSessionTest extends TestCase
{
    private WorkoutSession $session;

    protected function setUp(): void
    {
        $this->session = new WorkoutSession();
    }

    #[DataProvider('typeLabelProvider')]
    public function testGetTypeLabelReturnsCorrectLabel(string $type, string $expected): void
    {
        $this->session->setType($type);
        $this->assertSame($expected, $this->session->getTypeLabel());
    }

    public static function typeLabelProvider(): array
    {
        return [
            ['push',  'Push — Brust, Schulter, Trizeps'],
            ['pull',  'Pull — Rücken, Bizeps'],
            ['legs',  'Beine — Komplett'],
            ['upper', 'Upper — Schwachpunkte'],
            ['other', 'other'],
        ];
    }

    #[DataProvider('typeColorProvider')]
    public function testGetTypeColorReturnsCorrectColor(string $type, string $expected): void
    {
        $this->session->setType($type);
        $this->assertSame($expected, $this->session->getTypeColor());
    }

    public static function typeColorProvider(): array
    {
        return [
            ['push',  '#C9184A'],
            ['pull',  '#2D6A4F'],
            ['legs',  '#E09F3E'],
            ['upper', '#4361EE'],
            ['other', '#888'],
        ];
    }

    public function testAddExerciseLogSetsSession(): void
    {
        $log = new ExerciseLog();
        $this->session->addExerciseLog($log);

        $this->assertSame($this->session, $log->getSession());
        $this->assertTrue($this->session->getExerciseLogs()->contains($log));
    }

    public function testAddExerciseLogDoesNotDuplicate(): void
    {
        $log = new ExerciseLog();
        $this->session->addExerciseLog($log);
        $this->session->addExerciseLog($log);

        $this->assertCount(1, $this->session->getExerciseLogs());
    }

    public function testRemoveExerciseLogNullsSession(): void
    {
        $log = new ExerciseLog();
        $this->session->addExerciseLog($log);
        $this->session->removeExerciseLog($log);

        $this->assertNull($log->getSession());
        $this->assertFalse($this->session->getExerciseLogs()->contains($log));
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $this->assertInstanceOf(\DateTimeInterface::class, $this->session->getCreatedAt());
        $this->assertEqualsWithDelta(time(), $this->session->getCreatedAt()->getTimestamp(), 2);
    }

    public function testSettersReturnStaticForChaining(): void
    {
        $date = new \DateTime();
        $this->assertSame($this->session, $this->session->setDate($date));
        $this->assertSame($this->session, $this->session->setType('push'));
        $this->assertSame($this->session, $this->session->setDurationMinutes(60));
        $this->assertSame($this->session, $this->session->setNotes('good session'));
    }

    public function testDurationAndNotesDefaultToNull(): void
    {
        $this->assertNull($this->session->getDurationMinutes());
        $this->assertNull($this->session->getNotes());
    }
}
