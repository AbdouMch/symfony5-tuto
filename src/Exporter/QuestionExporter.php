<?php

namespace App\Exporter;

use App\Entity\ExportStatus;
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
    private QuestionExportLimiter $questionExportLimiter;

    public function __construct(
        MessageBusInterface $bus,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        QuestionExportCache $cache,
        QuestionExportLimiter $questionExportLimiter
    ) {
        $this->cache = $cache;
        $this->bus = $bus;
        $this->em = $em;
        $this->translator = $translator;
        $this->questionExportLimiter = $questionExportLimiter;
    }

    public function create(User $user): Response
    {
        $limiter = $this->questionExportLimiter->getLimiter($user);
        $limit = $limiter->consume();

        if (false === $limit->isAccepted()) {
            return new Response(
                $this->translator->trans('export.create.too_many_request.title', [], 'export'),
                $this->translator->trans(
                    'export.create.too_many_request.message',
                    ['{retry_after}' => $limit->getRetryAfter()->format('d/m/Y H:i')],
                    'export'
                ),
                null,
                null,
                'too many requests'
            );
        }

        $export = $this->cache->getExportForUser($user);
        $completedStatus = $this->em->getRepository(ExportStatus::class)->findOneByConstantCode(ExportStatus::COMPLETED);

        if (null !== $export) {
            if ($completedStatus->getConstantCode() === $export->getStatus()->getConstantCode()) {
                $response = new Response(
                    $this->translator->trans('export.create.complete.title', [], 'export'),
                    $this->translator->trans('export.create.complete.message', ['{filename}' => $export->getResult()], 'export'),
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
        $pendingStatus = $this->em->getRepository(ExportStatus::class)->findOneByConstantCode(ExportStatus::PENDING);

        $export = ExportFactory::create(Question::class, $user->getId(), null);
        $export->setStatus($pendingStatus);

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
