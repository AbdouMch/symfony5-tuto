<?php

namespace App\Repository;

use App\Entity\ExportStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExportStatus>
 *
 * @method ExportStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExportStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExportStatus[]    findAll()
 * @method ExportStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExportStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExportStatus::class);
    }

    public function add(ExportStatus $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ExportStatus $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByConstantCode(string $constantCode): ExportStatus
    {
        $resultCacheId = str_replace('\\', '-', __CLASS__);

        return $this->createQueryBuilder('e')
            ->andWhere('e.constantCode = :constant_code')
            ->setParameter('constant_code', $constantCode)
            ->getQuery()
            ->enableResultCache(28800, $resultCacheId)
            ->getOneOrNullResult()
        ;
    }
}
