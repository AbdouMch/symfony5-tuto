<?php

namespace App\DataList;

final class DataListInput
{
    private int $limit;
    private int $page;
    private string $sort;
    private string $sortBy;
    private array $filters;

    private function __construct(int $limit, int $page, string $sort, string $sortBy, array $filters)
    {
        $this->limit = $limit;
        $this->page = $page;
        $this->sort = $sort;
        $this->sortBy = $sortBy;
        $this->filters = $filters;
    }

    /**
     * @param array $params          raw query params — $paramFetcher->all() or $request->query->all()
     * @param array $knownFieldNames from getFields(); used to whitelist filters and validate sort_by
     */
    public static function fromArray(
        array $params,
        array $knownFieldNames,
        string $defaultSortBy,
        int $defaultLimit = 23
    ): self {
        $limit = max(1, (int) ($params['limit'] ?? $defaultLimit));
        $page = max(1, (int) ($params['page'] ?? 1));
        $sort = in_array($params['sort'] ?? '', ['asc', 'desc'], true) ? $params['sort'] : 'asc';
        $sortBy = in_array($params['sort_by'] ?? '', $knownFieldNames, true)
            ? $params['sort_by']
            : $defaultSortBy;

        $filters = [];
        foreach ($knownFieldNames as $fieldName) {
            if (isset($params[$fieldName]) && '' !== $params[$fieldName]) {
                $filters[$fieldName] = $params[$fieldName];
            }
        }

        return new self($limit, $page, $sort, $sortBy, $filters);
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }
}
