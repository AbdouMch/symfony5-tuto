<?php

namespace App\DataList;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractField
{
    protected string $rootAlias;
    protected QueryBuilder $qb;

    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;
        $this->rootAlias = $qb->getRootAliases()[0];
//        $qb->addSelect($this->getField());
        $this->addJoins($qb);
    }

    abstract public function getDefaultFilter(): string;

    abstract public function getField(): string;

    abstract protected function addJoins(QueryBuilder $qb): QueryBuilder;

    protected function hasJoin(QueryBuilder $queryBuilder, string $joinType, string $join, string $joinAlias): bool
    {
        return stripos($queryBuilder->getDQL(), "$joinType join $join $joinAlias");
    }

    protected function leftJoin(string $rootAlias, $join, $joinAlias): void
    {
        if ($this->hasJoin($this->qb, Join::LEFT_JOIN, "$rootAlias.$join", $joinAlias)) {
            return;
        }

        $this->qb->leftJoin($join, $joinAlias);
    }

    protected function innerJoin(string $rootAlias, $join, $joinAlias): void
    {
        $join = "$rootAlias.$join";

        if ($this->hasJoin($this->qb, Join::INNER_JOIN, $join, $joinAlias)) {
            return;
        }

        $this->qb->leftJoin($join, $joinAlias);
    }
}
