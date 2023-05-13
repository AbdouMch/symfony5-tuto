<?php

namespace App\Entity;

use App\Repository\ExportStatusRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ExportStatusRepository::class, readOnly=true)
 */
class ExportStatus
{
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';
    public const PENDING = 'pending';
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $constantCode;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getConstantCode(): string
    {
        return $this->constantCode;
    }

    public function setConstantCode(string $constantCode): self
    {
        $this->constantCode = $constantCode;

        return $this;
    }
}
