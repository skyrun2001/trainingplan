<?php

namespace App\Tests\Unit\Service;

use App\Entity\TrainingPlan;
use App\Entity\User;
use App\Repository\TrainingPlanRepository;
use App\Service\DefaultPlanSeeder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DefaultPlanSeederTest extends TestCase
{
    private EntityManagerInterface  $em;
    private TrainingPlanRepository  $planRepo;
    private DefaultPlanSeeder       $seeder;
    private User                    $user;

    protected function setUp(): void
    {
        $this->em       = $this->createMock(EntityManagerInterface::class);
        $this->planRepo = $this->createMock(TrainingPlanRepository::class);
        $this->seeder   = new DefaultPlanSeeder($this->em, $this->planRepo);

        $this->user = new User();
        $this->user->setUsername('athlete');
    }

    public function testSeedIfNeededReturnsExistingPlan(): void
    {
        $existing = new TrainingPlan();
        $this->planRepo->expects($this->once())
            ->method('findActiveForUser')
            ->with($this->user)
            ->willReturn($existing);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $result = $this->seeder->seedIfNeeded($this->user);

        $this->assertSame($existing, $result);
    }

    public function testSeedIfNeededCreatesPlanWhenNoneExists(): void
    {
        $this->planRepo->expects($this->once())
            ->method('findActiveForUser')
            ->willReturn(null);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $result = $this->seeder->seedIfNeeded($this->user);

        $this->assertInstanceOf(TrainingPlan::class, $result);
    }

    public function testSeedIfNeededCreatesActiveplan(): void
    {
        $this->planRepo->method('findActiveForUser')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');

        $plan = $this->seeder->seedIfNeeded($this->user);

        $this->assertTrue($plan->isActive());
    }

    public function testSeedIfNeededSetsPlanName(): void
    {
        $this->planRepo->method('findActiveForUser')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');

        $plan = $this->seeder->seedIfNeeded($this->user);

        $this->assertSame('4-Tage Split', $plan->getName());
    }

    public function testSeedIfNeededCreatesFourDays(): void
    {
        $this->planRepo->method('findActiveForUser')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');

        $plan = $this->seeder->seedIfNeeded($this->user);

        $this->assertCount(4, $plan->getDays());
    }

    public function testSeedIfNeededDayTypesAreCorrect(): void
    {
        $this->planRepo->method('findActiveForUser')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');

        $plan  = $this->seeder->seedIfNeeded($this->user);
        $types = $plan->getDays()->map(fn($d) => $d->getType())->toArray();

        $this->assertContains('push',  $types);
        $this->assertContains('pull',  $types);
        $this->assertContains('legs',  $types);
        $this->assertContains('upper', $types);
    }

    public function testSeedIfNeededEachDayHasExercises(): void
    {
        $this->planRepo->method('findActiveForUser')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');

        $plan = $this->seeder->seedIfNeeded($this->user);

        foreach ($plan->getDays() as $day) {
            $this->assertGreaterThan(0, $day->getExercises()->count(),
                "Day '{$day->getType()}' should have exercises"
            );
        }
    }

    public function testSeedIfNeededAssignsPlanToUser(): void
    {
        $this->planRepo->method('findActiveForUser')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');

        $plan = $this->seeder->seedIfNeeded($this->user);

        $this->assertSame($this->user, $plan->getUser());
    }
}
