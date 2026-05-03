<?php

namespace App\Entity;

use App\Repository\TrainingPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrainingPlanRepository::class)]
#[ORM\Table(name: 'training_plan')]
class TrainingPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'trainingPlans')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 120)]
    private string $name = '4-Tage Split';

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(targetEntity: PlanDay::class, mappedBy: 'plan', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $days;

    public function __construct()
    {
        $this->days      = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $n): static { $this->name = $n; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $a): static { $this->isActive = $a; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function getDays(): Collection { return $this->days; }

    public function addDay(PlanDay $day): static
    {
        if (!$this->days->contains($day)) {
            $this->days->add($day);
            $day->setPlan($this);
        }
        return $this;
    }

    public function removeDay(PlanDay $day): static
    {
        $this->days->removeElement($day);
        return $this;
    }

    public function getDayByType(string $type): ?PlanDay
    {
        foreach ($this->days as $day) {
            if ($day->getType() === $type) return $day;
        }
        return null;
    }
}
