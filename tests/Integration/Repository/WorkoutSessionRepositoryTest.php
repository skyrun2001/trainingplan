<?php

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Entity\WorkoutSession;
use App\Repository\WorkoutSessionRepository;
use App\Tests\Integration\IntegrationTestCase;

class WorkoutSessionRepositoryTest extends IntegrationTestCase
{
    private WorkoutSessionRepository $repo;
    private User                     $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(WorkoutSessionRepository::class);

        $this->user = new User();
        $this->user->setUsername('athlete');
        $this->user->setPassword('hashed');
        $this->persist($this->user);
    }

    private function makeSession(string $dateStr, string $type = 'push'): WorkoutSession
    {
        $s = new WorkoutSession();
        $s->setDate(new \DateTime($dateStr));
        $s->setType($type);
        $s->setUser($this->user);
        return $s;
    }

    // ── getCurrentStreakForUser ─────────────────────────────────────────────────

    public function testStreakIsZeroWithNoSessions(): void
    {
        $this->assertSame(0, $this->repo->getCurrentStreakForUser($this->user));
    }

    public function testStreakOfOneForTodayOnly(): void
    {
        $this->persist($this->makeSession('today'));
        $this->assertSame(1, $this->repo->getCurrentStreakForUser($this->user));
    }

    public function testStreakOfOneForYesterdayOnly(): void
    {
        $this->persist($this->makeSession('yesterday'));
        $this->assertSame(1, $this->repo->getCurrentStreakForUser($this->user));
    }

    public function testStreakIsZeroWhenLastSessionIsOlderThanYesterday(): void
    {
        $this->persist($this->makeSession('-3 days'));
        $this->assertSame(0, $this->repo->getCurrentStreakForUser($this->user));
    }

    public function testStreakOfThreeConsecutiveDaysEndingToday(): void
    {
        $this->persist(
            $this->makeSession('today'),
            $this->makeSession('yesterday'),
            $this->makeSession('-2 days'),
        );
        $this->assertSame(3, $this->repo->getCurrentStreakForUser($this->user));
    }

    public function testStreakBreaksOnGap(): void
    {
        $this->persist(
            $this->makeSession('today'),
            $this->makeSession('yesterday'),
            $this->makeSession('-3 days'), // gap on -2 days breaks it
        );
        $this->assertSame(2, $this->repo->getCurrentStreakForUser($this->user));
    }

    public function testMultipleSessionsSameDayCountOnce(): void
    {
        $this->persist(
            $this->makeSession('today', 'push'),
            $this->makeSession('today', 'pull'),
        );
        $this->assertSame(1, $this->repo->getCurrentStreakForUser($this->user));
    }

    // ── findRecentForUser ──────────────────────────────────────────────────────

    public function testFindRecentForUserReturnsSessionsOrderedByDateDesc(): void
    {
        $this->persist(
            $this->makeSession('-2 days', 'legs'),
            $this->makeSession('yesterday', 'push'),
            $this->makeSession('today', 'pull'),
        );

        $results = $this->repo->findRecentForUser($this->user, 10);

        $this->assertCount(3, $results);
        $this->assertSame('pull', $results[0]->getType());
        $this->assertSame('push', $results[1]->getType());
        $this->assertSame('legs', $results[2]->getType());
    }

    public function testFindRecentForUserRespectsLimit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->persist($this->makeSession("-{$i} days"));
        }

        $results = $this->repo->findRecentForUser($this->user, 3);
        $this->assertCount(3, $results);
    }

    public function testFindRecentForUserDoesNotReturnOtherUsersSession(): void
    {
        $other = new User();
        $other->setUsername('other');
        $other->setPassword('hash');
        $this->persist($other);

        $foreignSession = new WorkoutSession();
        $foreignSession->setDate(new \DateTime('today'));
        $foreignSession->setType('push');
        $foreignSession->setUser($other);
        $this->persist($foreignSession);

        $results = $this->repo->findRecentForUser($this->user, 10);
        $this->assertCount(0, $results);
    }

    // ── findByDateRangeAndUser ─────────────────────────────────────────────────

    public function testFindByDateRangeReturnsSessionsInRange(): void
    {
        $this->persist(
            $this->makeSession('-10 days'),
            $this->makeSession('-5 days'),
            $this->makeSession('-1 day'),
        );

        $results = $this->repo->findByDateRangeAndUser(
            new \DateTime('-7 days'),
            new \DateTime('today'),
            $this->user
        );

        $this->assertCount(2, $results);
    }

    // ── countByTypeForUser ─────────────────────────────────────────────────────

    public function testCountByTypeReturnsCorrectCounts(): void
    {
        $this->persist(
            $this->makeSession('today',     'push'),
            $this->makeSession('yesterday', 'push'),
            $this->makeSession('-2 days',   'pull'),
        );

        $counts = $this->repo->countByTypeForUser($this->user);
        $map    = array_column($counts, 'cnt', 'type');

        $this->assertEquals(2, $map['push']);
        $this->assertEquals(1, $map['pull']);
        $this->assertArrayNotHasKey('legs', $map);
    }
}
