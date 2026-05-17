<?php

namespace App\Tests\Unit\Entity;

use App\Entity\PlanExercise;
use PHPUnit\Framework\TestCase;

class PlanExerciseTest extends TestCase
{
    private PlanExercise $exercise;

    protected function setUp(): void
    {
        $this->exercise = new PlanExercise();
        $this->exercise->setName('Bench Press');
        $this->exercise->setSection('Hauptteil');
        $this->exercise->setDefaultSets(4);
        $this->exercise->setDefaultReps(8);
        $this->exercise->setProgressionNote('go heavier');
        $this->exercise->setIsNew(true);
        $this->exercise->setSortOrder(2);
    }

    public function testToArrayContainsAllFields(): void
    {
        $arr = $this->exercise->toArray();

        $this->assertArrayHasKey('id',              $arr);
        $this->assertArrayHasKey('name',            $arr);
        $this->assertArrayHasKey('section',         $arr);
        $this->assertArrayHasKey('defaultSets',     $arr);
        $this->assertArrayHasKey('defaultReps',     $arr);
        $this->assertArrayHasKey('progressionNote', $arr);
        $this->assertArrayHasKey('isNew',           $arr);
        $this->assertArrayHasKey('sortOrder',       $arr);
    }

    public function testToArrayReturnsCorrectValues(): void
    {
        $arr = $this->exercise->toArray();

        $this->assertNull($arr['id']); // not persisted
        $this->assertSame('Bench Press',  $arr['name']);
        $this->assertSame('Hauptteil',    $arr['section']);
        $this->assertSame(4,              $arr['defaultSets']);
        $this->assertSame(8,              $arr['defaultReps']);
        $this->assertSame('go heavier',   $arr['progressionNote']);
        $this->assertTrue($arr['isNew']);
        $this->assertSame(2,              $arr['sortOrder']);
    }

    public function testDefaultValues(): void
    {
        $ex = new PlanExercise();
        $this->assertSame('',          $ex->getName());
        $this->assertSame('Hauptteil', $ex->getSection());
        $this->assertSame(3,           $ex->getDefaultSets());
        $this->assertNull($ex->getDefaultReps());
        $this->assertNull($ex->getProgressionNote());
        $this->assertFalse($ex->isNew());
        $this->assertSame(0,           $ex->getSortOrder());
        $this->assertNull($ex->getDay());
        $this->assertNull($ex->getId());
    }

    public function testSettersReturnStaticForChaining(): void
    {
        $ex = new PlanExercise();
        $this->assertSame($ex, $ex->setName('foo'));
        $this->assertSame($ex, $ex->setSection('bar'));
        $this->assertSame($ex, $ex->setDefaultSets(3));
        $this->assertSame($ex, $ex->setDefaultReps(10));
        $this->assertSame($ex, $ex->setProgressionNote('note'));
        $this->assertSame($ex, $ex->setIsNew(false));
        $this->assertSame($ex, $ex->setSortOrder(0));
    }
}
