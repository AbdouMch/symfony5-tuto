<?php

namespace App\Exporter\Question;

use App\Entity\Export;
use App\Entity\ExportStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class QuestionExportCache extends RedisAdapter
{
    private const TTL = 3600;
    private EntityManagerInterface $em;

    public function __construct(\Redis $client, EntityManagerInterface $em)
    {
        parent::__construct($client, 'questions-export', self::TTL);
        $this->em = $em;
    }

    public function getExportForUser(User $user): ?Export
    {
        return $this->get((string) $user->getId(), function () use ($user) {
            return $this->em->getRepository(Export::class)
                ->findByUserAndStatus($user, [ExportStatus::PENDING, ExportStatus::IN_PROGRESS]);
        });
    }

    public function saveExportForUser(User $user, Export $export): void
    {
        $item = $this->getItem((string) $user->getId());

        $item->set($export);

        $this->save($item);
    }

    public function deleteExportForUser(User $user): void
    {
        $this->deleteItem((string) $user->getId());
    }
}
