<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Export;
use App\Entity\ExportStatus;
use App\Entity\User;
use App\Exporter\QuestionExportCache;
use App\Messenger\Message\QuestionExport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class QuestionExportHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;
    private QuestionExportCache $cache;

    public function __construct(EntityManagerInterface $em, QuestionExportCache $cache)
    {
        $this->em = $em;
        $this->cache = $cache;
    }

    public function __invoke(QuestionExport $message): void
    {
        /** @var Export|null $export */
        $export = $this->em->getRepository(Export::class)->find($message->getExportId());
        if (null === $export) {
            return;
        }
        // TODO add pdf export
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->find($export->getUserId());
        $filename = sprintf('/questions-%s.pdf', $user->getId());

        $completedStatus = $this->em->getRepository(ExportStatus::class)->findOneByConstantCode(ExportStatus::COMPLETED);

        $export->setStatus($completedStatus)
            ->setResult($filename)
            ->setProgress(100);

        $this->em->flush();

        $this->cache->saveExportForUser($user, $export);
    }
}
