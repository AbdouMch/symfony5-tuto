<?php

namespace App\DataList\Filter;

final class FieldDefinition
{
    public string $column;
    public string $filterType;
    public string $defaultOperator;
    /** @var JoinDefinition[] */
    public array $joins;

    public function __construct(
        string $column,
        string $filterType,
        string $defaultOperator = 'eq',
        array $joins = []
    ) {
        $this->column = $column;
        $this->filterType = $filterType;
        $this->defaultOperator = $defaultOperator;
        $this->joins = $joins;
    }
}
