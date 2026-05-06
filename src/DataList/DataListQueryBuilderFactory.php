<?php

namespace App\DataList;

use App\DataList\Filter\FilterInterface;
use App\DataList\Filter\JoinDefinition;
use App\DataList\Filter\ScopeConstraint;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DataListQueryBuilderFactory
{
    private EntityManagerInterface $em;

    /** @var iterable|FilterInterface[] */
    private iterable $filters;

    public function __construct(EntityManagerInterface $em, iterable $filters)
    {
        $this->em      = $em;
        $this->filters = $filters;
    }

    public function createBaseQueryBuilder(
        DataListConfigurationInterface $config,
        string $sortBy = null,
        string $sort = 'ASC'
    ): QueryBuilder {
        $rootAlias = $config->getRootAlias();
        $sortBy    = $sortBy ?? $config->getDefaultSortBy();

        $qb = $this->em->getRepository($config->getEntityClass())
            ->createQueryBuilder($rootAlias)
            ->orderBy("$rootAlias.$sortBy", $sort);

        $nameGenerator = new QueryNameGenerator();
        $this->applyScope($qb, $config->getScope(), $nameGenerator);

        return $qb;
    }

    public function createFilteredQueryBuilder(
        DataListConfigurationInterface $config,
        array $filters,
        string $sortBy = null,
        string $sort = 'ASC'
    ): QueryBuilder {
        $rootAlias = $config->getRootAlias();
        $sortBy    = $sortBy ?? $config->getDefaultSortBy();

        $qb = $this->em->getRepository($config->getEntityClass())
            ->createQueryBuilder($rootAlias)
            ->orderBy("$rootAlias.$sortBy", $sort);

        $nameGenerator = new QueryNameGenerator();
        $this->applyScope($qb, $config->getScope(), $nameGenerator);
        $this->applyFilters($qb, $config, $filters, $nameGenerator);

        return $qb;
    }

    /**
     * @param ScopeConstraint[] $scope
     */
    private function applyScope(QueryBuilder $qb, array $scope, QueryNameGenerator $nameGenerator): void
    {
        foreach ($scope as $constraint) {
            $this->applyJoins($qb, $constraint->definition->joins);
            foreach ($this->filters as $filter) {
                if ($filter->getType() === $constraint->definition->filterType
                    && $filter->supports($constraint->operator)) {
                    $filter->apply($qb, $constraint->definition->column, $constraint->operator, $constraint->value, $nameGenerator);
                    break;
                }
            }
        }
    }

    private function applyFilters(
        QueryBuilder $qb,
        DataListConfigurationInterface $config,
        array $filters,
        QueryNameGenerator $nameGenerator
    ): void {
        $fields = $config->getFields();

        foreach ($filters as $fieldName => $fieldFilters) {
            if (!isset($fields[$fieldName]) || '' === $fieldFilters || [] === $fieldFilters) {
                continue;
            }

            $definition = $fields[$fieldName];

            $this->applyJoins($qb, $definition->joins);

            $operators = is_array($fieldFilters)
                ? $fieldFilters
                : [$definition->defaultOperator => $fieldFilters];

            foreach ($operators as $operator => $value) {
                $applied = false;
                foreach ($this->filters as $filter) {
                    if ($filter->getType() === $definition->filterType && $filter->supports($operator)) {
                        $filter->apply($qb, $definition->column, $operator, (string) $value, $nameGenerator);
                        $applied = true;
                        break;
                    }
                }
                if (!$applied) {
                    throw new HttpException(
                        Response::HTTP_BAD_REQUEST,
                        sprintf('No filter supports type "%s" with operator "%s".', $definition->filterType, $operator)
                    );
                }
            }
        }
    }

    /**
     * @param JoinDefinition[] $joins
     */
    private function applyJoins(QueryBuilder $qb, array $joins): void
    {
        foreach ($joins as $join) {
            if ($this->hasJoin($qb, $join)) {
                continue;
            }
            $method = 'left' === $join->type ? 'leftJoin' : 'innerJoin';
            $qb->$method($join->relation, $join->alias);
        }
    }

    private function hasJoin(QueryBuilder $qb, JoinDefinition $join): bool
    {
        foreach ($qb->getDQLPart('join') as $joinGroup) {
            foreach ($joinGroup as $existing) {
                if ($existing->getJoin() === $join->relation && $existing->getAlias() === $join->alias) {
                    return true;
                }
            }
        }

        return false;
    }
}
