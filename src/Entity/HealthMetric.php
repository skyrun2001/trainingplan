<?php

namespace App\Entity;

use App\Repository\HealthMetricRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HealthMetricRepository::class)]
#[ORM\UniqueConstraint(name: 'uq_health_metric', fields: ['user', 'date', 'metricKey'])]
#[ORM\Index(name: 'idx_health_metric_user_date', fields: ['user', 'date'])]
class HealthMetric
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

    /** Metric identifier, e.g. "steps", "weight_kg", "heart_rate_avg" */
    #[ORM\Column(length: 100)]
    private string $metricKey;

    #[ORM\Column(type: Types::FLOAT)]
    private float $metricValue;

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getDate(): \DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): static { $this->date = $date; return $this; }

    public function getMetricKey(): string { return $this->metricKey; }
    public function setMetricKey(string $key): static { $this->metricKey = $key; return $this; }

    public function getMetricValue(): float { return $this->metricValue; }
    public function setMetricValue(float $value): static { $this->metricValue = $value; return $this; }
}
