<?php

namespace App\DataList\Filter;

use App\DataList\QueryNameGenerator;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NumberFilter implements FilterInterface
{
    private const OPERATORS = ['eq', 'neq', 'lt', 'gt', 'lte', 'gte', 'in'];

    public function getType(): string
    {
        return FilterType::NUMBER;
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
            case 'in':
                $values = json_decode($value, true);
                if (!is_array($values)) {
                    throw new HttpException(Response::HTTP_BAD_REQUEST, 'value should be an array');
                }
                $qb->andWhere("$column IN (:$param)")->setParameter($param, $values, Connection::PARAM_STR_ARRAY);
                break;
            default:
                throw new HttpException(Response::HTTP_BAD_REQUEST, sprintf('Operator "%s" is not supported by NumberFilter.', $operator));
        }
    }

    public function supports(string $operator): bool
    {
        return in_array($operator, self::OPERATORS, true);
    }
}
