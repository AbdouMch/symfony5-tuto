<?php

namespace App\DataList;

class Result
{
    private array $result;
    private int $limit;
    private int $page;
    private int $totalCount;
    private int $filteredCount;

    public function __construct(array $result, int $limit, int $page, int $totalCount, int $filteredCount)
    {
        $this->result = $result;
        $this->limit = $limit;
        $this->page = $page;
        $this->totalCount = $totalCount;
        $this->filteredCount = $filteredCount;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getFilteredCount(): int
    {
        return $this->filteredCount;
    }
}
