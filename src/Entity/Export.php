<?php

namespace App\Entity;

use App\Repository\ExportRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ExportRepository::class)
 */
class Export
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $entity;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $progress = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $userId;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $data;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $result;

    /**
     * @ORM\ManyToOne(targetEntity=ExportStatus::class)
     *
     * @ORM\JoinColumn(name="export_status_id", referencedColumnName="id", nullable=true)
     */
    private ExportStatus $status;

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function setEntity(string $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getStatus(): ExportStatus
    {
        return $this->status;
    }

    public function setStatus(ExportStatus $status): self
    {
        $this->status = $status;

        return $this;
    }
}
