<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    public const LAST_UPDATED_CACHE_KEY = 'question_last_updated_at';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function add(Question $question, bool $flush = false): void
    {
        $this->getEntityManager()->persist($question);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Question $question, bool $flush = false): void
    {
        $this->getEntityManager()->remove($question);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function createAskedOrderedByNewestQueryBuilder(): QueryBuilder
    {
        return $this->addIsAskedQueryBuilder()
            ->orderBy('q.askedAt', 'DESC')
        ;
    }

    public function findTopNewestQuestions(int $count): array
    {
        $qb = $this->createAskedOrderedByNewestQueryBuilder();
        $qb = $this->addTopVotesQueryBuilder($qb);
        $qb = $this->addIsAskedQueryBuilder($qb);

        return $qb->setMaxResults($count)
            ->getQuery()
            ->getResult();
    }

    public function getLastUpdatedAt(): ?\DateTime
    {
        $lastUpdatedAt = $this->createQueryBuilder('e')
            ->select('e.updatedAt')
            ->orderBy('e.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->enableResultCache(750, self::LAST_UPDATED_CACHE_KEY)
            ->getOneOrNullResult()
        ;

        if (null !== $lastUpdatedAt) {
            return current($lastUpdatedAt);
        }

        return null;
    }

    public function deleteCachedKey(string $cacheKey): void
    {
        $cache = $this->_em->getConfiguration()->getResultCache();

        if (null !== $cache) {
            $cache->deleteItem($cacheKey);
        }
    }

    private function addIsAskedQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        return $this->getOrCreateQueryBuilder($qb)
            ->andWhere('q.askedAt IS NOT NULL');
    }

    private function addTopVotesQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        return $this->getOrCreateQueryBuilder($qb)
            ->addOrderBy('q.votes', 'ASC');
    }

    private function getOrCreateQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        return $qb ?: $this->createQueryBuilder('q');
    }
}
