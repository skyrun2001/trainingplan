<?php

namespace App\Entity;

use App\Repository\ExerciseLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExerciseLogRepository::class)]
#[ORM\Table(name: 'exercise_log')]
class ExerciseLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkoutSession::class, inversedBy: 'exerciseLogs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?WorkoutSession $session = null;

    #[ORM\Column(length: 120)]
    private ?string $exerciseName = null;

    #[ORM\Column]
    private int $setNumber = 1;

    #[ORM\Column(nullable: true)]
    private ?int $reps = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $weightKg = null;

    #[ORM\Column(nullable: true)]
    private ?int $rpe = null; // 1-10 perceived effort

    public function getId(): ?int { return $this->id; }

    public function getSession(): ?WorkoutSession { return $this->session; }
    public function setSession(?WorkoutSession $session): static { $this->session = $session; return $this; }

    public function getExerciseName(): ?string { return $this->exerciseName; }
    public function setExerciseName(string $name): static { $this->exerciseName = $name; return $this; }

    public function getSetNumber(): int { return $this->setNumber; }
    public function setSetNumber(int $n): static { $this->setNumber = $n; return $this; }

    public function getReps(): ?int { return $this->reps; }
    public function setReps(?int $reps): static { $this->reps = $reps; return $this; }

    public function getWeightKg(): ?string { return $this->weightKg; }
    public function setWeightKg(?string $w): static { $this->weightKg = $w; return $this; }

    public function getRpe(): ?int { return $this->rpe; }
    public function setRpe(?int $rpe): static { $this->rpe = $rpe; return $this; }
}
