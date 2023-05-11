<?php

namespace App\Exporter;

use App\Entity\Export;

class Response
{
    private string $title;
    private string $message;
    private ?Export $export;
    private ?string $error;
    /**
     * @var mixed
     */
    private $extra;

    /**
     * @param mixed $extra
     */
    public function __construct(string $title, string $message, ?Export $export, $extra, ?string $error)
    {
        $this->title = $title;
        $this->message = $message;
        $this->export = $export;
        $this->error = $error;
        $this->extra = $extra;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getExport(): ?Export
    {
        return $this->export;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }
}
