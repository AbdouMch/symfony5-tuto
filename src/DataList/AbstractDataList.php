<?php

namespace App\DataList;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    public function list(ParamFetcher $paramFetcher): Result
    {
        $limit = $paramFetcher->get('limit', true);
        $page = $paramFetcher->get('page', true);
        $order = $paramFetcher->get('sort', true);
        $orderBy = $paramFetcher->get('sort_by', true);

        $options = $this->configureOptions([
            'filters' => $this->getFilters($paramFetcher),
        ]);

        // prepare the offset
        $offset = ($page - 1) * $limit;

        // init query builder
        $rootAlias = $this->getRootAlias();
        $qb = $this->entityRepository
            ->createQueryBuilder($rootAlias)
            ->orderBy("$rootAlias.$orderBy", $order)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $this->addCriteria($qb, $options['filters']);

        // get result
        $spells = $qb
            ->getQuery()
            ->getResult();

        return new Result(
            $spells,
            $limit,
            $page,
            $this->totalCount(),
            $this->filteredCount($qb)
        );
    }

    abstract protected function getRootAlias(): string;

    abstract protected function getDataFieldsClasses(): array;

    protected function getFilters(ParamFetcher $paramFetcher): array
    {
        $filters = [];
        $params = $paramFetcher->all();
        foreach ($this->getFields() as $field) {
            if (isset($params[$field])) {
                $filters[$field] = $params[$field];
            }
        }

        return $filters;
    }

    protected function configureOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'visible_fields' => $this->getFields(),
            'filters' => [],
        ]);

        return $resolver->resolve($options);
    }

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

        if (isset($fields[$fieldName])) {
            return new $fields[$fieldName]($qb);
        }

        return $fields[$fieldName] ?? null;
    }

    private function addCriteria(QueryBuilder $qb, array $filters): QueryBuilder
    {
        $i = 1;

        foreach ($filters as $field => $fieldFilters) {
            $dataField = $this->getDataField($field, $qb);

            // skip empty filters or unmapped fields
            if (null === $dataField || empty($fieldFilters)) {
                continue;
            }

            if (\is_array($fieldFilters)) {
                foreach ($fieldFilters as $operator => $value) {
                    $parameter = $field.'_'.$operator.'_param_'.$i;
                    $qb = $this->andWhere($qb, $dataField->getField(), $operator, $parameter, $value);
                }
            } else {
                $operator = $dataField->getDefaultFilter();
                $clause = $this->createCriteria($operator, $field, $fieldFilters);
                $qb->addCriteria($clause);
            }
            ++$i;
        }

        return $qb;
    }

    private function createCriteria(string $operator, string $field, $value): Criteria
    {
        return Criteria::create()
            ->andWhere(Criteria::expr()->$operator($field, $value));
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
