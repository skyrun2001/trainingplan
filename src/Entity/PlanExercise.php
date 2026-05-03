<?php

namespace App\Entity;

use App\Repository\PlanExerciseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanExerciseRepository::class)]
#[ORM\Table(name: 'plan_exercise')]
class PlanExercise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PlanDay::class, inversedBy: 'exercises')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PlanDay $day = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column(length: 80)]
    private string $section = 'Hauptteil';

    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column]
    private int $defaultSets = 3;

    #[ORM\Column(nullable: true)]
    private ?int $defaultReps = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $progressionNote = null;

    #[ORM\Column]
    private bool $isNew = false;

    public function getId(): ?int { return $this->id; }

    public function getDay(): ?PlanDay { return $this->day; }
    public function setDay(?PlanDay $day): static { $this->day = $day; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $s): static { $this->sortOrder = $s; return $this; }

    public function getSection(): string { return $this->section; }
    public function setSection(string $s): static { $this->section = $s; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $n): static { $this->name = $n; return $this; }

    public function getDefaultSets(): int { return $this->defaultSets; }
    public function setDefaultSets(int $s): static { $this->defaultSets = $s; return $this; }

    public function getDefaultReps(): ?int { return $this->defaultReps; }
    public function setDefaultReps(?int $r): static { $this->defaultReps = $r; return $this; }

    public function getProgressionNote(): ?string { return $this->progressionNote; }
    public function setProgressionNote(?string $n): static { $this->progressionNote = $n; return $this; }

    public function isNew(): bool { return $this->isNew; }
    public function setIsNew(bool $n): static { $this->isNew = $n; return $this; }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'section'         => $this->section,
            'defaultSets'     => $this->defaultSets,
            'defaultReps'     => $this->defaultReps,
            'progressionNote' => $this->progressionNote,
            'isNew'           => $this->isNew,
            'sortOrder'       => $this->sortOrder,
        ];
    }
}
