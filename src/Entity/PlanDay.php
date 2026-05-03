<?php

namespace App\Entity;

use App\Repository\PlanDayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanDayRepository::class)]
#[ORM\Table(name: 'plan_day')]
class PlanDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TrainingPlan::class, inversedBy: 'days')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TrainingPlan $plan = null;

    #[ORM\Column(length: 30)]
    private string $type = 'push'; // push|pull|legs|upper

    #[ORM\Column(length: 120)]
    private string $label = '';

    #[ORM\Column(length: 120)]
    private string $color = '#C9184A';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $focus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\OneToMany(targetEntity: PlanExercise::class, mappedBy: 'day', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $exercises;

    public function __construct()
    {
        $this->exercises = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getPlan(): ?TrainingPlan { return $this->plan; }
    public function setPlan(?TrainingPlan $plan): static { $this->plan = $plan; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }

    public function getLabel(): string { return $this->label; }
    public function setLabel(string $l): static { $this->label = $l; return $this; }

    public function getColor(): string { return $this->color; }
    public function setColor(string $c): static { $this->color = $c; return $this; }

    public function getFocus(): ?string { return $this->focus; }
    public function setFocus(?string $f): static { $this->focus = $f; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $n): static { $this->note = $n; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $s): static { $this->sortOrder = $s; return $this; }

    public function getExercises(): Collection { return $this->exercises; }

    public function addExercise(PlanExercise $ex): static
    {
        if (!$this->exercises->contains($ex)) {
            $this->exercises->add($ex);
            $ex->setDay($this);
        }
        return $this;
    }

    public function removeExercise(PlanExercise $ex): static
    {
        $this->exercises->removeElement($ex);
        return $this;
    }

    public function getExercisesAsArray(): array
    {
        return array_map(fn(PlanExercise $e) => [
            'name'            => $e->getName(),
            'defaultSets'     => $e->getDefaultSets(),
            'defaultReps'     => $e->getDefaultReps(),
            'section'         => $e->getSection(),
            'progressionNote' => $e->getProgressionNote(),
            'isNew'           => $e->isNew(),
            'id'              => $e->getId(),
        ], $this->exercises->toArray());
    }
}
