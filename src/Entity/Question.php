<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=QuestionRepository::class)
 */
class Question
{
    use TimestampableEntity;
    /**
     * @ORM\Id()
     *
     * @ORM\GeneratedValue()
     *
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank(message="question.title.not_blank")
     *
     * @Assert\Length(min=4, minMessage="question.title.min_length")
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     *
     * @Assert\NotBlank(message="question.content.not_blank")
     *
     * @Assert\Length(min=4, minMessage="question.content.min_length")
     */
    private $question;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $askedAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $votes = 0;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="questions")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private User $owner;

    /**
     * The question is about a spell.
     *
     * @ORM\ManyToOne(targetEntity=Spell::class, inversedBy="questions")
     */
    private ?Spell $spell;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="pendingQuestions")
     *
     * @ORM\JoinTable(
     *     name="questions_to_users",
     *     joinColumns={@ORM\JoinColumn(name="question_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $toUsers;

    /**
     * @ORM\Column(type="smallint", options={"unsigned": true})
     *
     * @ORM\Version()
     */
    private int $version = 1;

    public function __construct()
    {
        $this->toUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getAskedAt(): ?\DateTimeInterface
    {
        return $this->askedAt;
    }

    public function setAskedAt(?\DateTimeInterface $askedAt): self
    {
        $this->askedAt = $askedAt;

        return $this;
    }

    public function getVotes(): int
    {
        return $this->votes;
    }

    public function getVotesString(): string
    {
        $prefix = $this->getVotes() >= 0 ? '+' : '-';

        return sprintf('%s %d', $prefix, abs($this->getVotes()));
    }

    public function setVotes(int $votes): self
    {
        $this->votes = $votes;

        return $this;
    }

    public function upVote(): self
    {
        ++$this->votes;

        return $this;
    }

    public function downVote(): self
    {
        --$this->votes;

        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getSpell(): ?Spell
    {
        return $this->spell;
    }

    public function setSpell(?Spell $spell): self
    {
        $this->spell = $spell;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getToUsers()
    {
        return $this->toUsers;
    }

    public function addToUser(User $toUser): self
    {
        if (!$this->toUsers->contains($toUser)) {
            $this->toUsers[] = $toUser;
        }

        return $this;
    }

    public function removeToUser(User $toUser): self
    {
        $this->toUsers->removeElement($toUser);

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }
}
