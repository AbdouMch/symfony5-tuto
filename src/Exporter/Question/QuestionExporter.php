<?php

namespace App\Exporter\Question;

use App\Entity\ExportStatus;
use App\Entity\Question;
use App\Entity\User;
use App\Exporter\Response;
use App\Factory\ExportFactory;
use App\Messenger\Message\QuestionExport;
use App\Service\DateTimeService;
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
    private DateTimeService $dateTimeService;

    public function __construct(
        MessageBusInterface    $bus,
        EntityManagerInterface $em,
        TranslatorInterface    $translator,
        QuestionExportCache    $cache,
        QuestionExportLimiter  $questionExportLimiter,
        DateTimeService        $dateTimeService
    )
    {
        $this->cache = $cache;
        $this->bus = $bus;
        $this->em = $em;
        $this->translator = $translator;
        $this->questionExportLimiter = $questionExportLimiter;
        $this->dateTimeService = $dateTimeService;
    }

    public function create(User $user): Response
    {
        $limiter = $this->questionExportLimiter->getLimiter($user);
        $limit = $limiter->consume();

        if (false === $limit->isAccepted()) {
            $retryAfter = $limit
                ->getRetryAfter()
                ->setTimezone(
                    $this->dateTimeService->getUserTimezone()
                );
            return new Response(
                $this->translator->trans('export.create.too_many_request.title', [], 'export'),
                $this->translator->trans(
                    'export.create.too_many_request.message',
                    ['{retry_after}' => $retryAfter->format('d/m/Y H:i')],
                    'export'
                ),
                null,
                null,
                'too many requests'
            );
        }

        $export = $this->cache->getExportForUser($user);
        $completedStatus = $this->em->getRepository(ExportStatus::class)->findOneByConstantCode(ExportStatus::COMPLETED);
        $questionsLastUpdatedAt = $this->em->getRepository(Question::class)->getLastUpdatedAt();

        if (null !== $export && $questionsLastUpdatedAt < $export->getCreatedAt()) {
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
