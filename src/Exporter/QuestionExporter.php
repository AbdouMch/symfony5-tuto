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
    private QuestionExportCache $cache;

    public function __construct(
        MessageBusInterface $bus,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        QuestionExportCache $cache
    ) {
        $this->cache = $cache;
        $this->bus = $bus;
        $this->em = $em;
        $this->translator = $translator;
    }

    public function create(User $user): Response
    {
        $export = $this->cache->getExportForUser($user);

        if (null !== $export) {
            if (Export::COMPLETE === $export->getStatus()) {
                $response = new Response(
                    $this->translator->trans('export.create.complete.title', [], 'export'),
                    $this->translator->trans('export.create.complete.message', ['{filename}' => $export->getData()], 'export'),
                    $export,
                    null,
                    'available export file'
                );
            } else {
                $response = new Response(
                    $this->translator->trans('export.create.pending.title', [], 'export'),
                    $this->translator->trans('export.create.pending.message', [], 'export'),
                    $export,
                    null,
                    'export already launched'
                );
            }

            return $response;
        }

        $export = ExportFactory::create(Question::class, $user->getId(), null);
        $this->em->persist($export);
        $this->em->flush();

        $this->cache->saveExportForUser($user, $export);

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
