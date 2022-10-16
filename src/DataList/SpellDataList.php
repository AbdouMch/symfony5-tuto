<?php

namespace App\DataList;

use App\DataList\DataField\Spell\NameField;
use App\DataList\DataField\Spell\OwnerField;
use App\Repository\SpellRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpellDataList
{
    private SpellRepository $spellRepository;

    public function __construct(SpellRepository $spellRepository)
    {
        $this->spellRepository = $spellRepository;
    }

    public function configureOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'visible_fields' => [
                'name',
                'constantCode',
            ],
            'filters' => [],
        ]);

        return $resolver->resolve($options);
    }

    public function list(int $limit, int $page, string $orderBy, ?string $order, array $options): Result
    {
        $options = $this->configureOptions($options);

        // prepare the offset
        $offset = ($page - 1) * $limit;

        // init query builder
        $qb = $this->spellRepository
            ->createQueryBuilder('spell')
            ->orderBy("spell.$orderBy", $order)
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
            count($spells)
        );
    }

    protected function totalCount(): int
    {
        return $this->spellRepository->createQueryBuilder('spell')
            ->select('COUNT(spell.id)')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return string field class name
     */
    protected function getField(string $fieldName): ?string
    {
        $fields = [
            'name' => NameField::class,
            'owner' => OwnerField::class
        ];

        return $fields[$fieldName] ?? null;
    }

    private function addCriteria(QueryBuilder $qb, array $filters): QueryBuilder
    {
        $i = 1;

        foreach ($filters as $field => $fieldFilters) {
            $dataFieldClass = $this->getField($field);

            // add joins and select
            /** @var AbstractField $dataField */
            $dataField = new $dataFieldClass($qb);
            // skip empty filters
            if (empty($fieldFilters)) {
                continue;
            }

            if (\is_array($fieldFilters)) {
                foreach ($fieldFilters as $operator => $value) {
                    $parameter = $field . '_' . $operator . '_param_' . $i;
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
        string       $field,
        string       $operator,
        string       $parameter,
        string       $value
    ): QueryBuilder
    {
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
                    '%' . strtolower($value) . '%');
                break;
            case 'startsWith':
                $qb->andWhere("LOWER($field) LIKE :$parameter")->setParameter($parameter,
                    strtolower($value) . '%');
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
                    '%' . strtolower($value));
                break;
            case 'gtOrNull':
                $qb->andWhere("$field > :$parameter OR $field IS NULL")->setParameter($parameter, $value);
                break;
            case 'equalDate':
                $date = new \DateTime($value);
                $qb->andWhere($qb->expr()->between("$field", ':date_start', ':date_end'))
                    ->setParameter('date_start', $date->format('Y-m-d 00:00:00'))
                    ->setParameter('date_end', $date->format('Y-m-d 23:59:59'));
                break;
            default:
                throw new HttpException(Response::HTTP_BAD_REQUEST, 'Unknown comparison operator: ' . $operator);
        }

        return $qb;
    }
}
