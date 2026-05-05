<?php

namespace App\DataList\Filter;

use App\DataList\QueryNameGenerator;
use Doctrine\ORM\QueryBuilder;

interface FilterInterface
{
    public function getType(): string;

    public function apply(QueryBuilder $qb, string $column, string $operator, string $value, QueryNameGenerator $nameGenerator): void;

    public function supports(string $operator): bool;
}
