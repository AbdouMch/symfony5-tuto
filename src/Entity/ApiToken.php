<?php

namespace App\Entity;

use App\Repository\ApiTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass=ApiTokenRepository::class)
 */
class ApiToken
{
    public const DELIMITER = '.';

    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=16, unique=true)
     */
    private string $identifier;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private string $hashedSecret;

    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(type="datetime_immutable", options={"default": "CURRENT_TIMESTAMP"})
     */
    private \DateTimeImmutable $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="apiTokens")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /**
     * Holds the plaintext token only immediately after construction — null once discarded.
     */
    private ?string $plainToken = null;

    public function __construct(User $user)
    {
        $this->user = $user;

        $this->identifier = bin2hex(random_bytes(8));
        $secret = bin2hex(random_bytes(32));
        $this->hashedSecret = hash('sha256', $secret);
        $this->plainToken = $this->identifier.self::DELIMITER.$secret;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Returns the full plaintext token (identifier.secret).
     * Only populated immediately after construction; null after that.
     */
    public function getPlainToken(): ?string
    {
        return $this->plainToken;
    }

    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->hashedSecret, hash('sha256', $secret));
    }
}
