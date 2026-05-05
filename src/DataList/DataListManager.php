<?php

namespace App\DataList;

use App\DataList\Filter\FilterInterface;
use App\DataList\Filter\JoinDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DataListManager
{
    private EntityManagerInterface $em;

    /** @var iterable|FilterInterface[] */
    private iterable $filters;

    public function __construct(EntityManagerInterface $em, iterable $filters)
    {
        $this->em = $em;
        $this->filters = $filters;
    }

    public function list(DataListConfigurationInterface $config, array $params): Result
    {
        $input = DataListInput::fromArray(
            $params,
            array_keys($config->getFields()),
            $config->getDefaultSortBy()
        );

        $rootAlias = $config->getRootAlias();

        $qb = $this->em->getRepository($config->getEntityClass())
            ->createQueryBuilder($rootAlias)
            ->orderBy("$rootAlias.{$input->getSortBy()}", $input->getSort());

        $nameGenerator = new QueryNameGenerator();
        $this->applyFilters($qb, $config, $input->getFilters(), $nameGenerator);

        $offset = ($input->getPage() - 1) * $input->getLimit();

        $items = (clone $qb)
            ->setFirstResult($offset)
            ->setMaxResults($input->getLimit())
            ->getQuery()
            ->getResult();

        return new Result(
            $items,
            $input->getLimit(),
            $input->getPage(),
            $this->totalCount($config),
            $this->filteredCount($qb, $rootAlias)
        );
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

    private function totalCount(DataListConfigurationInterface $config): int
    {
        $rootAlias = $config->getRootAlias();

        return (int) $this->em->getRepository($config->getEntityClass())
            ->createQueryBuilder($rootAlias)
            ->select("COUNT($rootAlias.id)")
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function filteredCount(QueryBuilder $qb, string $rootAlias): int
    {
        return (int) (clone $qb)
            ->resetDQLParts(['orderBy'])
            ->select("COUNT($rootAlias.id)")
            ->setFirstResult(null)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }
}