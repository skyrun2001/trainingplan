<?php

namespace App\Tests\Unit\Entity;

use App\Entity\PlanDay;
use App\Entity\TrainingPlan;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class TrainingPlanTest extends TestCase
{
    private TrainingPlan $plan;

    protected function setUp(): void
    {
        $this->plan = new TrainingPlan();
    }

    public function testDefaultValues(): void
    {
        $this->assertSame('4-Tage Split', $this->plan->getName());
        $this->assertTrue($this->plan->isActive());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->plan->getCreatedAt());
        $this->assertNull($this->plan->getId());
        $this->assertNull($this->plan->getUser());
        $this->assertCount(0, $this->plan->getDays());
    }

    public function testAddDaySetsPlan(): void
    {
        $day = new PlanDay();
        $this->plan->addDay($day);

        $this->assertSame($this->plan, $day->getPlan());
        $this->assertTrue($this->plan->getDays()->contains($day));
    }

    public function testAddDayDoesNotDuplicate(): void
    {
        $day = new PlanDay();
        $this->plan->addDay($day);
        $this->plan->addDay($day);

        $this->assertCount(1, $this->plan->getDays());
    }

    public function testRemoveDayRemovesFromCollection(): void
    {
        $day = new PlanDay();
        $this->plan->addDay($day);
        $this->plan->removeDay($day);

        $this->assertFalse($this->plan->getDays()->contains($day));
    }

    public function testGetDayByTypeFindsCorrectDay(): void
    {
        $push = (new PlanDay())->setType('push');
        $pull = (new PlanDay())->setType('pull');
        $this->plan->addDay($push);
        $this->plan->addDay($pull);

        $this->assertSame($push, $this->plan->getDayByType('push'));
        $this->assertSame($pull, $this->plan->getDayByType('pull'));
    }

    public function testGetDayByTypeReturnsNullWhenNotFound(): void
    {
        $day = (new PlanDay())->setType('push');
        $this->plan->addDay($day);

        $this->assertNull($this->plan->getDayByType('legs'));
    }

    public function testSettersReturnStaticForChaining(): void
    {
        $user = new User();
        $this->assertSame($this->plan, $this->plan->setUser($user));
        $this->assertSame($this->plan, $this->plan->setName('My Plan'));
        $this->assertSame($this->plan, $this->plan->setIsActive(false));
    }

    public function testSetUserAndGet(): void
    {
        $user = new User();
        $this->plan->setUser($user);
        $this->assertSame($user, $this->plan->getUser());
    }
}
