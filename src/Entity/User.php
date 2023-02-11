<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 *
 * @UniqueEntity(fields={"email"}, message="registration.email.unique")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private string $email;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $password;

    private ?string $plainPassword;

    /**
     * @ORM\OneToMany(targetEntity=ApiToken::class, mappedBy="user", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private Collection $apiTokens;

    /**
     * @ORM\OneToMany(targetEntity=Question::class, mappedBy="owner", orphanRemoval=true)
     */
    private Collection $questions;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isVerified = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isBlocked = false;

    /**
     * @ORM\Column(name="totpSecret", type="string", nullable=true)
     */
    private ?string $totpSecret;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isTotpEnabled = false;

    /**
     * @ORM\OneToMany(targetEntity=Spell::class, mappedBy="owner")
     */
    private Collection $spells;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $agreedTermsAt;

    /**
     * @ORM\ManyToMany(targetEntity=Question::class, mappedBy="toUsers")
     */
    private Collection $pendingQuestions;

    public function __construct()
    {
        $this->apiTokens = new ArrayCollection();
        $this->questions = new ArrayCollection();
        $this->spells = new ArrayCollection();
        $this->pendingQuestions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getAvatar(string $size): string
    {
        return 'https://ui-avatars.com/api/?'.http_build_query(
            [
                'name' => $this->firstName,
                'size' => $size,
                'background' => 'random',
            ]
        );
    }

    /**
     * @return Collection<int, ApiToken>
     */
    public function getApiTokens(): Collection
    {
        return $this->apiTokens;
    }

    public function addApiToken(ApiToken $apiToken): self
    {
        if (!$this->apiTokens->contains($apiToken)) {
            $this->apiTokens[] = $apiToken;
        }

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions[] = $question;
            $question->setOwner($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question) && $question->getOwner() === $this) {
            $question->setOwner(null);
        }

        return $this;
    }

    public function isIsVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isIsBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): self
    {
        $this->isBlocked = $isBlocked;

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return isset($this->totpSecret) && $this->isTotpEnabled;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret;

        return $this;
    }

    public function isIsTotpEnabled(): ?bool
    {
        return $this->isTotpEnabled;
    }

    public function setIsTotpEnabled(bool $isTotpEnabled): self
    {
        $this->isTotpEnabled = $isTotpEnabled;

        return $this;
    }

    /**
     * @return Collection<int, Spell>
     */
    public function getSpells(): Collection
    {
        return $this->spells;
    }

    public function addSpell(Spell $spell): self
    {
        if (!$this->spells->contains($spell)) {
            $this->spells[] = $spell;
            $spell->setOwner($this);
        }

        return $this;
    }

    public function removeSpell(Spell $spell): self
    {
        if ($this->spells->removeElement($spell) && $spell->getOwner() === $this) {
            $spell->setOwner(null);
        }

        return $this;
    }

    public function getAgreedTermsAt(): \DateTimeImmutable
    {
        return $this->agreedTermsAt;
    }

    public function agreeTerms(): self
    {
        $this->agreedTermsAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getPendingQuestions(): Collection
    {
        return $this->pendingQuestions;
    }

    public function setPendingQuestions(ArrayCollection $questions): self
    {
        $this->pendingQuestions = $questions;

        return $this;
    }

    public function addPendingQuestion(Question $pendingQuestion): self
    {
        if (!$this->pendingQuestions->contains($pendingQuestion)) {
            $this->pendingQuestions[] = $pendingQuestion;
            $pendingQuestion->addToUser($this);
        }

        return $this;
    }

    public function removePendingQuestion(Question $pendingQuestion): self
    {
        if ($this->pendingQuestions->removeElement($pendingQuestion)) {
            $pendingQuestion->removeToUser($this);
        }

        return $this;
    }
}
