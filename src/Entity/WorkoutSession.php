<?php

namespace App\Entity;

use App\Repository\WorkoutSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkoutSessionRepository::class)]
#[ORM\Table(name: 'workout_session')]
class WorkoutSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null; // push, pull, legs, upper

    #[ORM\Column(nullable: true)]
    private ?int $durationMinutes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $score = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'workoutSessions')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: ExerciseLog::class, mappedBy: 'session', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['exerciseName' => 'ASC', 'setNumber' => 'ASC'])]
    private Collection $exerciseLogs;

    public function __construct()
    {
        $this->exerciseLogs = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getDate(): ?\DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): static { $this->date = $date; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getDurationMinutes(): ?int { return $this->durationMinutes; }
    public function setDurationMinutes(?int $d): static { $this->durationMinutes = $d; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function getScore(): ?int { return $this->score; }
    public function setScore(?int $score): static { $this->score = $score; return $this; }

    public function getExerciseLogs(): Collection { return $this->exerciseLogs; }

    public function addExerciseLog(ExerciseLog $log): static
    {
        if (!$this->exerciseLogs->contains($log)) {
            $this->exerciseLogs->add($log);
            $log->setSession($this);
        }
        return $this;
    }

    public function removeExerciseLog(ExerciseLog $log): static
    {
        if ($this->exerciseLogs->removeElement($log)) {
            if ($log->getSession() === $this) {
                $log->setSession(null);
            }
        }
        return $this;
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'push'  => 'Push — Brust, Schulter, Trizeps',
            'pull'  => 'Pull — Rücken, Bizeps',
            'legs'  => 'Beine — Komplett',
            'upper' => 'Upper — Schwachpunkte',
            default => $this->type ?? '',
        };
    }

    public function getTypeColor(): string
    {
        return match($this->type) {
            'push'  => '#C9184A',
            'pull'  => '#2D6A4F',
            'legs'  => '#E09F3E',
            'upper' => '#4361EE',
            default => '#888',
        };
    }
}
