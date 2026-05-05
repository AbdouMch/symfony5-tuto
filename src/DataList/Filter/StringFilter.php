<?php

namespace App\DataList\Filter;

use App\DataList\QueryNameGenerator;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StringFilter implements FilterInterface
{
    private const OPERATORS = ['eq', 'neq', 'contains', 'startsWith', 'endsWith'];

    public function getType(): string
    {
        return FilterType::STRING;
    }

    public function apply(QueryBuilder $qb, string $column, string $operator, string $value, QueryNameGenerator $nameGenerator): void
    {
        $param = $nameGenerator->generate($column);

        switch ($operator) {
            case 'eq':
                $qb->andWhere("$column = :$param")->setParameter($param, $value);
                break;
            case 'neq':
                $qb->andWhere("$column != :$param")->setParameter($param, $value);
                break;
            case 'contains':
                $qb->andWhere("LOWER($column) LIKE :$param")->setParameter($param, '%'.strtolower($value).'%');
                break;
            case 'startsWith':
                $qb->andWhere("LOWER($column) LIKE :$param")->setParameter($param, strtolower($value).'%');
                break;
            case 'endsWith':
                $qb->andWhere("LOWER($column) LIKE :$param")->setParameter($param, '%'.strtolower($value));
                break;
            default:
                throw new HttpException(Response::HTTP_BAD_REQUEST, sprintf('Operator "%s" is not supported by StringFilter.', $operator));
        }
    }

    public function supports(string $operator): bool
    {
        return in_array($operator, self::OPERATORS, true);
    }
}
