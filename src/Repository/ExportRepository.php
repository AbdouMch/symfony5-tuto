<?php

namespace App\Repository;

use App\Entity\Export;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Export>
 *
 * @method Export|null find($id, $lockMode = null, $lockVersion = null)
 * @method Export|null findOneBy(array $criteria, array $orderBy = null)
 * @method Export[]    findAll()
 * @method Export[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Export::class);
    }

    public function add(Export $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Export $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUserAndStatus(User $user, array $status): ?Export
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.status', 'status')
            ->andWhere('e.userId = :user_id')
            ->setParameter('user_id', $user->getId())
            ->andWhere('status.constantCode in (:constant_codes)')
            ->setParameter('constant_codes', $status)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
