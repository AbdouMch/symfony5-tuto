<?php

namespace App\DataList;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class AbstractDataList
{
    private EntityRepository $entityRepository;

    public function __construct(EntityManagerInterface $em, string $entityClass)
    {
        $this->entityRepository = $em->getRepository($entityClass);
    }

    public function getFields(): array
    {
        return array_keys($this->getDataFieldsClasses());
    }

    public function buildInput(array $params): DataListInput
    {
        return DataListInput::fromArray($params, $this->getFields(), $this->getDefaultSortBy());
    }

    public function list(DataListInput $input): Result
    {
        $offset = ($input->getPage() - 1) * $input->getLimit();
        $qb = $this->getQueryBuilder($input->getFilters(), $input->getSortBy(), $input->getSort());

        $items = $qb
            ->setFirstResult($offset)
            ->setMaxResults($input->getLimit())
            ->getQuery()
            ->getResult();

        return new Result(
            $items,
            $input->getLimit(),
            $input->getPage(),
            $this->totalCount(),
            $this->filteredCount($qb)
        );
    }

    public function getQueryBuilder($filters, string $orderBy, string $order): QueryBuilder
    {
        $rootAlias = $this->getRootAlias();
        $qb = $this->entityRepository
            ->createQueryBuilder($rootAlias)
            ->orderBy("$rootAlias.$orderBy", $order)
        ;

        $this->addCriteria($qb, $filters);

        return $qb;
    }

    abstract protected function getRootAlias(): string;

    abstract protected function getDataFieldsClasses(): array;

    abstract protected function getDefaultSortBy(): string;

    protected function totalCount(): int
    {
        $rootAlias = $this->getRootAlias();

        return $this->entityRepository->createQueryBuilder($rootAlias)
            ->select("COUNT($rootAlias.id)")
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    protected function filteredCount(QueryBuilder $qb): int
    {
        $rootAlias = $this->getRootAlias();
        $qb->resetDQLParts(['orderBy']);

        return $qb
            ->select("COUNT($rootAlias.id)")
            ->setFirstResult(null)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    protected function getDataField(string $fieldName, QueryBuilder $qb): ?AbstractField
    {
        $fields = $this->getDataFieldsClasses();

        if (!isset($fields[$fieldName])) {
            return null;
        }

        return new $fields[$fieldName]($qb);
    }

    private function addCriteria(QueryBuilder $qb, array $filters): QueryBuilder
    {
        $i = 1;

        foreach ($filters as $field => $fieldFilters) {
            $dataField = $this->getDataField($field, $qb);

            if (null === $dataField || '' === $fieldFilters || [] === $fieldFilters) {
                ++$i;
                continue;
            }

            if (is_array($fieldFilters)) {
                foreach ($fieldFilters as $operator => $value) {
                    $parameter = $field.'_'.$operator.'_param_'.$i;
                    $qb = $this->andWhere($qb, $dataField->getField(), $operator, $parameter, $value);
                }
            } else {
                $operator = $dataField->getDefaultFilter();
                $parameter = $field.'_'.$operator.'_param_'.$i;
                $qb = $this->andWhere($qb, $dataField->getField(), $operator, $parameter, $fieldFilters);
            }
            ++$i;
        }

        return $qb;
    }

    private function andWhere(
        QueryBuilder $qb,
        string $field,
        string $operator,
        string $parameter,
        string $value
    ): QueryBuilder {
        switch ($operator) {
            case 'eq':
                $qb->andWhere("$field = :$parameter")->setParameter($parameter, $value);
                break;
            case 'lt':
                $qb->andWhere("$field < :$parameter")->setParameter($parameter, $value);
                break;
            case 'gt':
                $qb->andWhere("$field > :$parameter")->setParameter($parameter, $value);
                break;
            case 'lte':
                $qb->andWhere("$field <= :$parameter")->setParameter($parameter, $value);
                break;
            case 'gte':
                $qb->andWhere("$field >= :$parameter")->setParameter($parameter, $value);
                break;
            case 'neq':
                $qb->andWhere("$field != :$parameter")->setParameter($parameter, $value);
                break;
            case 'contains':
                $qb->andWhere("LOWER($field) LIKE :$parameter")->setParameter($parameter,
                    '%'.strtolower($value).'%');
                break;
            case 'startsWith':
                $qb->andWhere("LOWER($field) LIKE :$parameter")->setParameter($parameter,
                    strtolower($value).'%');
                break;
            case 'in':
                $value = json_decode($value, true);
                if (!is_array($value)) {
                    throw new HttpException(Response::HTTP_BAD_REQUEST, 'value should be an array');
                }
                $qb->andWhere("$field IN (:$parameter)")->setParameter($parameter, $value, Connection::PARAM_STR_ARRAY);
                break;
            case 'endsWith':
                $qb->andWhere("LOWER($field) LIKE :$parameter")->setParameter($parameter,
                    '%'.strtolower($value));
                break;
            case 'gtOrNull':
                $qb->andWhere("$field > :$parameter OR $field IS NULL")->setParameter($parameter, $value);
                break;
            default:
                throw new HttpException(Response::HTTP_BAD_REQUEST, 'Unknown comparison operator: '.$operator);
        }

        return $qb;
    }
}
