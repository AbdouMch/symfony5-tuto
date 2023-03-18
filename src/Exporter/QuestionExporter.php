<?php

namespace App\Exporter;

use App\Entity\Export;
use App\Entity\Question;
use App\Entity\User;
use App\Factory\ExportFactory;
use App\Messenger\Message\QuestionExport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuestionExporter
{
    private MessageBusInterface $bus;
    private EntityManagerInterface $em;
    private TranslatorInterface $translator;

    public function __construct(
        MessageBusInterface $bus,
        EntityManagerInterface $em,
        TranslatorInterface $translator
    ) {
        $this->bus = $bus;
        $this->em = $em;
        $this->translator = $translator;
    }

    public function create(User $user): Response
    {
        $userId = $user->getId();

        $exportRepository = $this->em->getRepository(Export::class);

        $export = $exportRepository->findOneBy([
            'userId' => $userId,
            'entity' => Question::class,
            'status' => [Export::PENDING, Export::IN_PROGRESS],
        ]);

        if (null !== $export) {
            return new Response(
                $this->translator->trans('export.create.pending.title', [], 'export'),
                $this->translator->trans('export.create.pending.message', ['%entity%' => 'question'], 'export'),
                $export,
                null,
                'export already launched'
            );
        }

        $export = ExportFactory::create(Question::class, $userId, null);
        $this->em->persist($export);
        $this->em->flush();

        $this->bus->dispatch(
            new QuestionExport($export->getId())
        );

        return new Response(
            $this->translator->trans('export.create.success.title', [], 'export'),
            $this->translator->trans('export.create.success.message', ['%entity%' => 'question'], 'export'),
            $export,
            null,
            null
        );
    }
}
