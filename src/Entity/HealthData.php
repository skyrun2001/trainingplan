<?php

namespace App\Entity;

use App\Repository\HealthDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HealthDataRepository::class)]
#[ORM\UniqueConstraint(name: 'uq_health_user_date', fields: ['user', 'date'])]
class HealthData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $date;

    #[ORM\Column(nullable: true)]
    private ?int $steps = null;

    #[ORM\Column(nullable: true)]
    private ?int $sleepMinutes = null;

    #[ORM\Column(nullable: true)]
    private ?int $activeMinutes = null;

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getDate(): \DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): static { $this->date = $date; return $this; }

    public function getSteps(): ?int { return $this->steps; }
    public function setSteps(?int $steps): static { $this->steps = $steps; return $this; }

    public function getSleepMinutes(): ?int { return $this->sleepMinutes; }
    public function setSleepMinutes(?int $sleepMinutes): static { $this->sleepMinutes = $sleepMinutes; return $this; }

    public function getActiveMinutes(): ?int { return $this->activeMinutes; }
    public function setActiveMinutes(?int $activeMinutes): static { $this->activeMinutes = $activeMinutes; return $this; }

    public function toArray(): array
    {
        return [
            'date'          => $this->date->format('Y-m-d'),
            'steps'         => $this->steps,
            'sleepMinutes'  => $this->sleepMinutes,
            'activeMinutes' => $this->activeMinutes,
        ];
    }
}
