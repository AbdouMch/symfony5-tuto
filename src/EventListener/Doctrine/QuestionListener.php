<?php

namespace App\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class QuestionListener
{
    private HubInterface $mercureHub;

    public function __construct(HubInterface $hub)
    {
        $this->mercureHub = $hub;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $question = $args->getObject();

        $update = new Update(
            'questions_list',
            json_encode(['question_id' => $question->getId()]),
            false
        );

        $this->mercureHub->publish($update);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->postPersist($args);
    }
}
