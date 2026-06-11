<?php

namespace App\Entity;

use App\Repository\ApiTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
class ApiToken
{
    private const TTL_DAYS = 90;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** SHA-256 hash of the token — the raw value is only available right after creation */
    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    /** Raw token, shown to the client once; never persisted */
    private ?string $plainToken = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $expiresAt;

    public function __construct(User $user)
    {
        $this->plainToken = bin2hex(random_bytes(32));
        $this->token      = self::hashToken($this->plainToken);
        $this->user       = $user;
        $this->createdAt  = new \DateTime();
        $this->expiresAt  = (new \DateTime())->modify('+' . self::TTL_DAYS . ' days');
    }

    public static function hashToken(string $raw): string
    {
        return hash('sha256', $raw);
    }

    /** Upgrade a legacy plaintext-stored token to its hashed form. */
    public function rehash(): void
    {
        $this->token = self::hashToken($this->token);
    }

    public function getId(): ?int { return $this->id; }
    public function getToken(): string { return $this->token; }
    public function getPlainToken(): ?string { return $this->plainToken; }
    public function getUser(): User { return $this->user; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getExpiresAt(): \DateTimeInterface { return $this->expiresAt; }
    public function isExpired(): bool { return $this->expiresAt < new \DateTime(); }
}
