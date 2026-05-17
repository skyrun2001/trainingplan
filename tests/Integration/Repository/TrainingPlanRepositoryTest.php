<?php

namespace App\Tests\Integration\Repository;

use App\Entity\TrainingPlan;
use App\Entity\User;
use App\Repository\TrainingPlanRepository;
use App\Tests\Integration\IntegrationTestCase;

class TrainingPlanRepositoryTest extends IntegrationTestCase
{
    private TrainingPlanRepository $repo;
    private User                   $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(TrainingPlanRepository::class);

        $this->user = new User();
        $this->user->setUsername('planuser');
        $this->user->setPassword('hashed');
        $this->persist($this->user);
    }

    private function makePlan(bool $active = true, string $name = 'Test Plan'): TrainingPlan
    {
        $plan = new TrainingPlan();
        $plan->setUser($this->user);
        $plan->setName($name);
        $plan->setIsActive($active);
        return $plan;
    }

    public function testFindActiveForUserReturnsActivePlan(): void
    {
        $plan = $this->makePlan(true);
        $this->persist($plan);

        $found = $this->repo->findActiveForUser($this->user);

        $this->assertNotNull($found);
        $this->assertSame($plan->getName(), $found->getName());
    }

    public function testFindActiveForUserReturnsNullWhenNoPlan(): void
    {
        $this->assertNull($this->repo->findActiveForUser($this->user));
    }

    public function testFindActiveForUserIgnoresInactivePlan(): void
    {
        $inactive = $this->makePlan(false, 'Old Plan');
        $this->persist($inactive);

        $this->assertNull($this->repo->findActiveForUser($this->user));
    }

    public function testFindActiveForUserIgnoresOtherUsersPlan(): void
    {
        $other = new User();
        $other->setUsername('other');
        $other->setPassword('hash');
        $this->persist($other);

        $plan = new TrainingPlan();
        $plan->setUser($other);
        $plan->setName('Other Plan');
        $plan->setIsActive(true);
        $this->persist($plan);

        $this->assertNull($this->repo->findActiveForUser($this->user));
    }

    public function testFindActiveForUserReturnsOnlyOneEvenIfMultipleActive(): void
    {
        $this->persist($this->makePlan(true, 'Plan A'));
        $this->persist($this->makePlan(true, 'Plan B'));

        $found = $this->repo->findActiveForUser($this->user);

        $this->assertNotNull($found);
        $this->assertInstanceOf(TrainingPlan::class, $found);
    }
}
