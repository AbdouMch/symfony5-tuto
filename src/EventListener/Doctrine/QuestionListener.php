<?php

namespace App\EventListener\Doctrine;

use App\Entity\Question;
use App\Entity\User;
use App\Exporter\QuestionExportCache;
use App\Exporter\QuestionExportLimiter;
use App\Repository\QuestionRepository;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Security\Core\Security;

class QuestionListener
{
    private HubInterface $mercureHub;
    private QuestionExportCache $cache;
    private Security $security;
    private QuestionExportLimiter $questionExportLimiter;
    private QuestionRepository $questionRepository;

    public function __construct(
        HubInterface $hub,
        QuestionExportCache $cache,
        Security $security,
        QuestionExportLimiter $questionExportLimiter,
        QuestionRepository $questionRepository
    ) {
        $this->mercureHub = $hub;
        $this->cache = $cache;
        $this->security = $security;
        $this->questionExportLimiter = $questionExportLimiter;
        $this->questionRepository = $questionRepository;
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
        /** @var non-empty-string $data */
        $data = json_encode(['question_id' => $question->getId()], JSON_THROW_ON_ERROR);

        $update = new Update(
            'questions_list',
            $data,
            false
        );

        $this->mercureHub->publish($update);
        /** @var User $user */
        $user = $this->security->getUser();

        $this->cache->deleteExportForUser($user);
        $this->questionExportLimiter->reset($user);
        $this->questionRepository->deleteCachedKey(QuestionRepository::LAST_UPDATED_CACHE_KEY);
    }
}
