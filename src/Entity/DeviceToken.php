<?php

namespace App\Entity;

use App\Repository\DeviceTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeviceTokenRepository::class)]
#[ORM\Table(name: 'device_token')]
#[ORM\UniqueConstraint(name: 'uq_device_token', columns: ['token'])]
class DeviceToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** FCM registration token — globally unique per app installation */
    #[ORM\Column(length: 255)]
    private string $token = '';

    #[ORM\Column(length: 20)]
    private string $platform = 'android';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getToken(): string { return $this->token; }
    public function setToken(string $token): static { $this->token = $token; return $this; }

    public function getPlatform(): string { return $this->platform; }
    public function setPlatform(string $platform): static { $this->platform = $platform; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): static { $this->updatedAt = new \DateTimeImmutable(); return $this; }
}
