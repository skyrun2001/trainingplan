<?php

namespace App\Entity;

use App\Repository\SupplementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupplementRepository::class)]
#[ORM\Table(name: 'supplement')]
class Supplement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    /** Display dosage string, e.g. "5 g", "2 Kapseln", "2000 IU" */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $dosage = null;

    /** How many servings the user takes per day */
    #[ORM\Column]
    private int $servingsPerDay = 1;

    /** Current servings remaining */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $servingsRemaining = '0';

    /** Original pack size in servings — used for the % progress bar */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalServings = '0';

    /** Optional unit label shown in the UI, e.g. "Portionen", "g", "Tabletten" */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $unit = null;

    /** Alert when days of supply drops below this number */
    #[ORM\Column]
    private int $warningDays = 7;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    /**
     * When to take this supplement.
     * Valid values: 'morning', 'evening', 'pre_training', 'post_training'
     */
    #[ORM\Column(type: Types::JSON)]
    private array $schedule = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // ── Getters / setters ─────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getDosage(): ?string { return $this->dosage; }
    public function setDosage(?string $dosage): static { $this->dosage = $dosage; return $this; }

    public function getServingsPerDay(): int { return $this->servingsPerDay; }
    public function setServingsPerDay(int $v): static { $this->servingsPerDay = max(1, $v); return $this; }

    public function getServingsRemaining(): float { return (float) $this->servingsRemaining; }
    public function setServingsRemaining(float $v): static { $this->servingsRemaining = (string) max(0.0, $v); return $this; }

    public function getTotalServings(): float { return (float) $this->totalServings; }
    public function setTotalServings(float $v): static { $this->totalServings = (string) max(0.0, $v); return $this; }

    public function getUnit(): ?string { return $this->unit; }
    public function setUnit(?string $unit): static { $this->unit = $unit; return $this; }

    public function getWarningDays(): int { return $this->warningDays; }
    public function setWarningDays(int $v): static { $this->warningDays = max(1, $v); return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $v): static { $this->sortOrder = $v; return $this; }

    public function getSchedule(): array { return $this->schedule; }
    public function setSchedule(array $schedule): static
    {
        $valid = ['morning', 'evening', 'pre_training', 'post_training'];
        $this->schedule = array_values(array_intersect($schedule, $valid));
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    // ── Computed helpers ──────────────────────────────────────────────────────

    public function daysRemaining(): float
    {
        return $this->getServingsRemaining() / max(1, $this->servingsPerDay);
    }

    public function isEmpty(): bool
    {
        return $this->getServingsRemaining() <= 0.0;
    }

    public function isLow(): bool
    {
        return !$this->isEmpty() && $this->daysRemaining() <= $this->warningDays;
    }

    public function stockPercent(): float
    {
        $total = $this->getTotalServings();
        if ($total <= 0.0) {
            return 0.0;
        }
        return min(100.0, max(0.0, $this->getServingsRemaining() / $total * 100.0));
    }

    /** 'ok' | 'low' | 'empty' */
    public function stockStatus(): string
    {
        if ($this->isEmpty()) return 'empty';
        if ($this->isLow())   return 'low';
        return 'ok';
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'dosage'            => $this->dosage,
            'servingsPerDay'    => $this->servingsPerDay,
            'servingsRemaining' => $this->getServingsRemaining(),
            'totalServings'     => $this->getTotalServings(),
            'unit'              => $this->unit,
            'warningDays'       => $this->warningDays,
            'notes'             => $this->notes,
            'sortOrder'         => $this->sortOrder,
            'daysRemaining'     => round($this->daysRemaining(), 1),
            'stockPercent'      => round($this->stockPercent(), 1),
            'stockStatus'       => $this->stockStatus(),
            'isLow'             => $this->isLow(),
            'isEmpty'           => $this->isEmpty(),
            'schedule'          => $this->schedule,
        ];
    }
}
