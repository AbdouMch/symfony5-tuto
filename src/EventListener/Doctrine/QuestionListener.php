<?php

namespace App\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QuestionListener
{
    private HubInterface $mercureHub;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(HubInterface $hub, UrlGeneratorInterface $urlGenerator)
    {
        $this->mercureHub = $hub;
        $this->urlGenerator = $urlGenerator;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $question = $args->getObject();

        $update = new Update(
            $this->urlGenerator->generate('app_questions_partial_list'),
            json_encode(['question_id' => $question->getId()])
        );

        $this->mercureHub->publish($update);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->postPersist($args);
    }
}