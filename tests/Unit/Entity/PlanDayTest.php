<?php

namespace App\Tests\Unit\Entity;

use App\Entity\PlanDay;
use App\Entity\PlanExercise;
use PHPUnit\Framework\TestCase;

class PlanDayTest extends TestCase
{
    private PlanDay $day;

    protected function setUp(): void
    {
        $this->day = new PlanDay();
        $this->day->setType('push');
        $this->day->setLabel('Push Day');
        $this->day->setColor('#C9184A');
    }

    public function testAddExerciseSetsDay(): void
    {
        $ex = new PlanExercise();
        $this->day->addExercise($ex);

        $this->assertSame($this->day, $ex->getDay());
        $this->assertTrue($this->day->getExercises()->contains($ex));
    }

    public function testAddExerciseDoesNotDuplicate(): void
    {
        $ex = new PlanExercise();
        $this->day->addExercise($ex);
        $this->day->addExercise($ex);

        $this->assertCount(1, $this->day->getExercises());
    }

    public function testRemoveExerciseRemovesFromCollection(): void
    {
        $ex = new PlanExercise();
        $this->day->addExercise($ex);
        $this->day->removeExercise($ex);

        $this->assertFalse($this->day->getExercises()->contains($ex));
    }

    public function testGetExercisesAsArrayReturnsCorrectShape(): void
    {
        $ex = new PlanExercise();
        $ex->setName('Bench Press');
        $ex->setSection('Hauptteil');
        $ex->setDefaultSets(4);
        $ex->setDefaultReps(8);
        $ex->setProgressionNote('increase weight');
        $ex->setIsNew(true);

        $this->day->addExercise($ex);
        $arr = $this->day->getExercisesAsArray();

        $this->assertCount(1, $arr);
        $this->assertSame('Bench Press', $arr[0]['name']);
        $this->assertSame('Hauptteil',  $arr[0]['section']);
        $this->assertSame(4,            $arr[0]['defaultSets']);
        $this->assertSame(8,            $arr[0]['defaultReps']);
        $this->assertSame('increase weight', $arr[0]['progressionNote']);
        $this->assertTrue($arr[0]['isNew']);
        $this->assertArrayHasKey('id', $arr[0]);
    }

    public function testGetExercisesAsArrayEmptyWhenNoExercises(): void
    {
        $this->assertSame([], $this->day->getExercisesAsArray());
    }

    public function testDefaultValues(): void
    {
        $day = new PlanDay();
        $this->assertSame('push',     $day->getType());
        $this->assertSame('',         $day->getLabel());
        $this->assertSame('#C9184A',  $day->getColor());
        $this->assertNull($day->getFocus());
        $this->assertNull($day->getNote());
        $this->assertSame(0,          $day->getSortOrder());
    }

    public function testSettersReturnStaticForChaining(): void
    {
        $day = new PlanDay();
        $this->assertSame($day, $day->setType('pull'));
        $this->assertSame($day, $day->setLabel('Pull'));
        $this->assertSame($day, $day->setColor('#000'));
        $this->assertSame($day, $day->setFocus('focus'));
        $this->assertSame($day, $day->setNote('note'));
        $this->assertSame($day, $day->setSortOrder(1));
    }
}
