<?php

namespace App\Tests\Unit\Entity;

use App\Entity\HealthMetric;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class HealthMetricTest extends TestCase
{
    private HealthMetric $metric;
    private User         $user;

    protected function setUp(): void
    {
        $this->user   = new User();
        $this->user->setUsername('athlete');
        $this->metric = new HealthMetric();
        $this->metric->setUser($this->user);
        $this->metric->setDate(new \DateTime('2026-05-01'));
        $this->metric->setMetricKey('steps');
        $this->metric->setMetricValue(8500.0);
    }

    public function testGetUser(): void
    {
        $this->assertSame($this->user, $this->metric->getUser());
    }

    public function testGetDate(): void
    {
        $this->assertSame('2026-05-01', $this->metric->getDate()->format('Y-m-d'));
    }

    public function testGetMetricKey(): void
    {
        $this->assertSame('steps', $this->metric->getMetricKey());
    }

    public function testGetMetricValue(): void
    {
        $this->assertSame(8500.0, $this->metric->getMetricValue());
    }

    public function testSetMetricValue(): void
    {
        $this->metric->setMetricValue(9123.5);
        $this->assertSame(9123.5, $this->metric->getMetricValue());
    }

    public function testSetMetricKey(): void
    {
        $this->metric->setMetricKey('weight_kg');
        $this->assertSame('weight_kg', $this->metric->getMetricKey());
    }

    public function testIdIsNullBeforePersist(): void
    {
        $this->assertNull($this->metric->getId());
    }

    public function testSettersReturnStaticForChaining(): void
    {
        $m = new HealthMetric();
        $this->assertSame($m, $m->setUser($this->user));
        $this->assertSame($m, $m->setDate(new \DateTime()));
        $this->assertSame($m, $m->setMetricKey('calories_kcal'));
        $this->assertSame($m, $m->setMetricValue(2000.0));
    }
}
