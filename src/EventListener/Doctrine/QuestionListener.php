<?php

namespace App\EventListener\Doctrine;

use App\Contract\SilenceableListenerInterface;
use App\Contract\SilenceableListenerTrait;
use App\Entity\Question;
use App\Entity\User;
use App\Exporter\Question\QuestionExportCache;
use App\Exporter\Question\QuestionExportLimiter;
use App\Repository\QuestionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Security\Core\Security;

class QuestionListener implements SilenceableListenerInterface
{
    use SilenceableListenerTrait;

    private HubInterface $mercureHub;
    private QuestionExportCache $cache;
    private Security $security;
    private QuestionExportLimiter $questionExportLimiter;
    private QuestionRepository $questionRepository;
    private LoggerInterface $logger;

    public function __construct(
        HubInterface $hub,
        QuestionExportCache $cache,
        Security $security,
        QuestionExportLimiter $questionExportLimiter,
        QuestionRepository $questionRepository,
        LoggerInterface $logger
    ) {
        $this->mercureHub = $hub;
        $this->cache = $cache;
        $this->security = $security;
        $this->questionExportLimiter = $questionExportLimiter;
        $this->questionRepository = $questionRepository;
        $this->logger = $logger;
    }

    public function postPersist(Question $question): void
    {
        $this->handleUpdate($question);
    }

    public function postUpdate(Question $question): void
    {
        $this->handleUpdate($question);
    }

    private function handleUpdate(Question $question): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        /** @var non-empty-string $data */
        $data = json_encode(['question_id' => $question->getId()], JSON_THROW_ON_ERROR);

        $update = new Update(
            'questions_list',
            $data,
            false
        );

        try {
            $this->mercureHub->publish($update);
        } catch (\Throwable $e) {
            $this->logger->error('Error when publishing question update', ['exception' => $e]);
        }

        /** @var User $user */
        $user = $this->security->getUser();

        $this->cache->deleteExportForUser($user);
        $this->questionExportLimiter->reset($user);
        $this->questionRepository->deleteCachedKey(QuestionRepository::LAST_UPDATED_CACHE_KEY);
    }
}
