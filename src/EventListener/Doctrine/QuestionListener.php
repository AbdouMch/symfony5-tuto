<?php

namespace App\EventListener\Doctrine;

use App\Entity\Question;
use App\Entity\User;
use App\Exporter\QuestionExportCache;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Security\Core\Security;

class QuestionListener
{
    private HubInterface $mercureHub;
    private QuestionExportCache $cache;
    private Security $security;

    public function __construct(HubInterface $hub, QuestionExportCache $cache, Security $security)
    {
        $this->mercureHub = $hub;
        $this->cache = $cache;
        $this->security = $security;
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        /** @var Question $question */
        $question = $args->getObject();
        $this->handleUpdate($question);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        /** @var Question $question */
        $question = $args->getObject();
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
    }
}
