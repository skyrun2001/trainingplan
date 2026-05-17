<?php

namespace App\Tests\Integration\Repository;

use App\Entity\HealthMetric;
use App\Entity\User;
use App\Repository\HealthMetricRepository;
use App\Tests\Integration\IntegrationTestCase;

class HealthMetricRepositoryTest extends IntegrationTestCase
{
    private HealthMetricRepository $repo;
    private User                   $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(HealthMetricRepository::class);

        $this->user = new User();
        $this->user->setUsername('healthuser');
        $this->user->setPassword('hashed');
        $this->persist($this->user);
    }

    private function makeMetric(string $dateStr, string $key, float $value): HealthMetric
    {
        $m = new HealthMetric();
        $m->setUser($this->user);
        $m->setDate(new \DateTime($dateStr));
        $m->setMetricKey($key);
        $m->setMetricValue($value);
        return $m;
    }

    // ── findTodayForUser ───────────────────────────────────────────────────────

    public function testFindTodayReturnsEmptyArrayWhenNoData(): void
    {
        $this->assertSame([], $this->repo->findTodayForUser($this->user));
    }

    public function testFindTodayReturnsFlatMap(): void
    {
        $this->persist(
            $this->makeMetric('today', 'steps',     8500.0),
            $this->makeMetric('today', 'weight_kg', 78.5),
        );

        $result = $this->repo->findTodayForUser($this->user);

        $this->assertSame(8500.0, $result['steps']);
        $this->assertSame(78.5,   $result['weight_kg']);
    }

    public function testFindTodayIgnoresOtherDays(): void
    {
        $this->persist(
            $this->makeMetric('yesterday', 'steps', 9000.0),
            $this->makeMetric('today',     'steps', 8000.0),
        );

        $result = $this->repo->findTodayForUser($this->user);

        $this->assertSame(8000.0, $result['steps']);
        $this->assertCount(1, $result);
    }

    public function testFindTodayIgnoresOtherUsers(): void
    {
        $other = new User();
        $other->setUsername('other');
        $other->setPassword('hash');
        $this->persist($other);

        $otherMetric = new HealthMetric();
        $otherMetric->setUser($other);
        $otherMetric->setDate(new \DateTime('today'));
        $otherMetric->setMetricKey('steps');
        $otherMetric->setMetricValue(5000.0);
        $this->persist($otherMetric);

        $this->assertSame([], $this->repo->findTodayForUser($this->user));
    }

    // ── findRecentGroupedForUser ───────────────────────────────────────────────

    public function testFindRecentGroupedReturnsRowsGroupedByDate(): void
    {
        $this->persist(
            $this->makeMetric('-1 day', 'steps',     7000.0),
            $this->makeMetric('-1 day', 'weight_kg', 79.0),
            $this->makeMetric('-2 days', 'steps',    6500.0),
        );

        $result = $this->repo->findRecentGroupedForUser($this->user, 30);

        $this->assertCount(2, $result);
        $dates = array_column($result, 'date');
        $this->assertCount(2, array_unique($dates));
    }

    public function testFindRecentGroupedIncludesAllMetricsPerDate(): void
    {
        $this->persist(
            $this->makeMetric('today', 'steps',     8000.0),
            $this->makeMetric('today', 'weight_kg', 78.0),
            $this->makeMetric('today', 'sleep_minutes', 420.0),
        );

        $result = $this->repo->findRecentGroupedForUser($this->user, 7);

        $this->assertCount(1, $result);
        $row = $result[0];
        $this->assertSame(8000.0, $row['steps']);
        $this->assertSame(78.0,   $row['weight_kg']);
        $this->assertSame(420.0,  $row['sleep_minutes']);
    }

    public function testFindRecentGroupedOrdersOldestFirst(): void
    {
        $this->persist(
            $this->makeMetric('-2 days', 'steps', 5000.0),
            $this->makeMetric('today',   'steps', 8000.0),
            $this->makeMetric('-1 day',  'steps', 7000.0),
        );

        $result = $this->repo->findRecentGroupedForUser($this->user, 30);

        $this->assertCount(3, $result);
        $this->assertSame(5000.0, $result[0]['steps']);
        $this->assertSame(7000.0, $result[1]['steps']);
        $this->assertSame(8000.0, $result[2]['steps']);
    }

    public function testFindRecentGroupedRespectsDaysFilter(): void
    {
        $this->persist(
            $this->makeMetric('-40 days', 'steps', 5000.0),
            $this->makeMetric('today',    'steps', 8000.0),
        );

        $result = $this->repo->findRecentGroupedForUser($this->user, 30);

        $this->assertCount(1, $result);
        $this->assertSame(8000.0, $result[0]['steps']);
    }

    // ── findAllMetricKeysForUser ───────────────────────────────────────────────

    public function testFindAllMetricKeysReturnsDistinctKeysSorted(): void
    {
        $this->persist(
            $this->makeMetric('today',     'weight_kg', 78.0),
            $this->makeMetric('today',     'steps',     8000.0),
            $this->makeMetric('yesterday', 'steps',     7000.0),
        );

        $keys = $this->repo->findAllMetricKeysForUser($this->user);

        $this->assertSame(['steps', 'weight_kg'], $keys);
    }

    public function testFindAllMetricKeysReturnsEmptyForNewUser(): void
    {
        $this->assertSame([], $this->repo->findAllMetricKeysForUser($this->user));
    }

    // ── findOneByUserDateKey ───────────────────────────────────────────────────

    public function testFindOneByUserDateKeyFindsMetric(): void
    {
        $date   = new \DateTime('2026-05-01');
        $metric = $this->makeMetric('2026-05-01', 'steps', 9000.0);
        $this->persist($metric);

        $found = $this->repo->findOneByUserDateKey($this->user, $date, 'steps');

        $this->assertNotNull($found);
        $this->assertSame(9000.0, $found->getMetricValue());
    }

    public function testFindOneByUserDateKeyReturnsNullWhenNotFound(): void
    {
        $result = $this->repo->findOneByUserDateKey($this->user, new \DateTime('today'), 'nonexistent');
        $this->assertNull($result);
    }
}
