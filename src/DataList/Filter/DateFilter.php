<?php

namespace App\DataList\Filter;

use App\DataList\QueryNameGenerator;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DateFilter implements FilterInterface
{
    private const OPERATORS = ['eq', 'lt', 'gt', 'lte', 'gte', 'gtOrNull'];

    public function getType(): string
    {
        return FilterType::DATE;
    }

    public function apply(QueryBuilder $qb, string $column, string $operator, string $value, QueryNameGenerator $nameGenerator): void
    {
        $param = $nameGenerator->generate($column);

        switch ($operator) {
            case 'eq':
                $qb->andWhere("$column = :$param")->setParameter($param, $value);
                break;
            case 'lt':
                $qb->andWhere("$column < :$param")->setParameter($param, $value);
                break;
            case 'gt':
                $qb->andWhere("$column > :$param")->setParameter($param, $value);
                break;
            case 'lte':
                $qb->andWhere("$column <= :$param")->setParameter($param, $value);
                break;
            case 'gte':
                $qb->andWhere("$column >= :$param")->setParameter($param, $value);
                break;
            case 'gtOrNull':
                $qb->andWhere("$column > :$param OR $column IS NULL")->setParameter($param, $value);
                break;
            default:
                throw new HttpException(Response::HTTP_BAD_REQUEST, sprintf('Operator "%s" is not supported by DateFilter.', $operator));
        }
    }

    public function supports(string $operator): bool
    {
        return in_array($operator, self::OPERATORS, true);
    }
}
