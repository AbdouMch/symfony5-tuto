<?php

namespace App\Model;

class ApiResponse
{
    /**
     * @var mixed
     */
    private $result;
    private int $code;
    private ?int $limit;
    private ?int $page;

    public function __construct($result, int $code, ?int $limit = null, ?int $page = null)
    {
        $this->result = $result;
        $this->code = $code;
        $this->limit = $limit;
        $this->page = $page;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }
}
