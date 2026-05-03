<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80, unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::JSON)]
    private array $settings = [];

    #[ORM\OneToMany(targetEntity: TrainingPlan::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $trainingPlans;

    #[ORM\OneToMany(targetEntity: WorkoutSession::class, mappedBy: 'user')]
    private Collection $workoutSessions;

    public function __construct()
    {
        $this->trainingPlans   = new ArrayCollection();
        $this->workoutSessions = new ArrayCollection();
        $this->createdAt       = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $u): static { $this->username = $u; return $this; }

    public function getUserIdentifier(): string { return (string) $this->username; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $p): static { $this->password = $p; return $this; }

    public function eraseCredentials(): void {}

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    private function defaultSettings(): array
    {
        return [
            'weekSchedule' => ['1' => null, '2' => null, '3' => null, '4' => null, '5' => null, '6' => null, '7' => null],
            'reminderTime' => null,
        ];
    }

    public function getSettings(): array
    {
        return array_merge($this->defaultSettings(), $this->settings);
    }

    public function setSettings(array $s): static { $this->settings = $s; return $this; }

    public function getWeekSchedule(): array
    {
        return array_merge(
            $this->defaultSettings()['weekSchedule'],
            $this->getSettings()['weekSchedule'] ?? []
        );
    }

    public function getReminderTime(): ?string
    {
        return $this->getSettings()['reminderTime'] ?? null;
    }

    public function getTrainingPlans(): Collection { return $this->trainingPlans; }

    public function getActivePlan(): ?TrainingPlan
    {
        foreach ($this->trainingPlans as $plan) {
            if ($plan->isActive()) return $plan;
        }
        return null;
    }

    public function getWorkoutSessions(): Collection { return $this->workoutSessions; }
}
