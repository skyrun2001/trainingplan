<?php

namespace App\Tests\Unit\Entity;

use App\Entity\TrainingPlan;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setUsername('testuser');
    }

    public function testGetUserIdentifierReturnsUsername(): void
    {
        $this->assertSame('testuser', $this->user->getUserIdentifier());
    }

    public function testGetRolesAlwaysIncludesRoleUser(): void
    {
        $this->user->setRoles([]);
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    public function testGetRolesDeduplicates(): void
    {
        $this->user->setRoles(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_USER']);
        $roles = $this->user->getRoles();
        $this->assertSame(array_unique($roles), $roles);
    }

    public function testGetRolesMergesCustomRoles(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetSettingsReturnsDefaults(): void
    {
        $settings = $this->user->getSettings();
        $this->assertArrayHasKey('weekSchedule', $settings);
        $this->assertArrayHasKey('reminderTime', $settings);
        $this->assertNull($settings['reminderTime']);
    }

    public function testGetSettingsMergesUserValues(): void
    {
        $this->user->setSettings(['reminderTime' => '08:00']);
        $settings = $this->user->getSettings();
        $this->assertSame('08:00', $settings['reminderTime']);
        $this->assertArrayHasKey('weekSchedule', $settings);
    }

    public function testGetWeekScheduleReturnsArray(): void
    {
        $schedule = $this->user->getWeekSchedule();
        $this->assertIsArray($schedule);
        $this->assertNotEmpty($schedule);
    }

    public function testGetWeekScheduleDefaultsToAllNull(): void
    {
        $schedule = $this->user->getWeekSchedule();
        foreach ($schedule as $day) {
            $this->assertNull($day);
        }
    }

    public function testGetWeekScheduleReturnsValuesFromSettings(): void
    {
        // Settings weekSchedule values appear in the merged result
        $this->user->setSettings(['weekSchedule' => [1 => 'push', 2 => 'pull']]);
        $schedule = $this->user->getWeekSchedule();
        $this->assertContains('push', $schedule);
        $this->assertContains('pull', $schedule);
    }

    public function testGetReminderTimeDefaultsToNull(): void
    {
        $this->assertNull($this->user->getReminderTime());
    }

    public function testGetReminderTimeReturnsConfiguredValue(): void
    {
        $this->user->setSettings(['reminderTime' => '07:30']);
        $this->assertSame('07:30', $this->user->getReminderTime());
    }

    public function testGetActivePlanReturnsNullWhenNoPlans(): void
    {
        $this->assertNull($this->user->getActivePlan());
    }

    public function testGetActivePlanReturnsActivePlan(): void
    {
        $active = new TrainingPlan();
        $active->setUser($this->user);
        $active->setIsActive(true);
        $this->user->getTrainingPlans()->add($active);

        $this->assertSame($active, $this->user->getActivePlan());
    }

    public function testGetActivePlanSkipsInactivePlans(): void
    {
        $inactive = new TrainingPlan();
        $inactive->setUser($this->user);
        $inactive->setIsActive(false);
        $this->user->getTrainingPlans()->add($inactive);

        $this->assertNull($this->user->getActivePlan());
    }

    public function testEraseCredentialsDoesNothing(): void
    {
        $password = $this->user->getPassword();
        $this->user->eraseCredentials();
        $this->assertSame($password, $this->user->getPassword());
    }

    public function testConstructorSetsCreatedAt(): void
    {
        $user = new User();
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getCreatedAt());
        $this->assertEqualsWithDelta(time(), $user->getCreatedAt()->getTimestamp(), 2);
    }

    public function testSettersReturnStaticForChaining(): void
    {
        $user = new User();
        $this->assertSame($user, $user->setUsername('foo'));
        $this->assertSame($user, $user->setRoles([]));
        $this->assertSame($user, $user->setPassword('hash'));
        $this->assertSame($user, $user->setSettings([]));
    }
}
