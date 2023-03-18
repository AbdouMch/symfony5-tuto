<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Export;
use App\Messenger\Message\QuestionExport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class QuestionExportHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(QuestionExport $message): void
    {
        /** @var Export|null $export */
        $export = $this->em->getRepository(Export::class)->find($message->getExportId());
        if (null === $export) {
            return;
        }
        // TODO add pdf export
        $export->setStatus(Export::COMPLETE)
            ->setProgress(100);

        $this->em->flush();
    }
}
