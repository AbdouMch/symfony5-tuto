<?php

namespace App\DataList;

use Doctrine\ORM\QueryBuilder;

class DataListManager
{
    private DataListQueryBuilderFactory $factory;

    public function __construct(DataListQueryBuilderFactory $factory)
    {
        $this->factory = $factory;
    }

    public function list(DataListConfigurationInterface $config, array $params): Result
    {
        $input = DataListInput::fromArray(
            $params,
            array_keys($config->getFields()),
            $config->getDefaultSortBy()
        );

        $qb = $this->factory->createFilteredQueryBuilder(
            $config,
            $input->getFilters(),
            $input->getSortBy(),
            $input->getSort()
        );

        $rootAlias = $config->getRootAlias();
        $offset    = ($input->getPage() - 1) * $input->getLimit();

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

    private function totalCount(DataListConfigurationInterface $config): int
    {
        $rootAlias = $config->getRootAlias();

        return (int) $this->factory->createBaseQueryBuilder($config)
            ->resetDQLParts(['orderBy'])
            ->select("COUNT($rootAlias.id)")
            ->setFirstResult(null)
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
