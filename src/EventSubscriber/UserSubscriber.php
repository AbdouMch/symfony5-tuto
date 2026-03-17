<?php

namespace App\EventSubscriber;

use App\Contract\SilenceableListenerInterface;
use App\Contract\SilenceableListenerTrait;
use App\Entity\User;
use App\Service\Markdown\MarkdownConverterInterface;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

class UserSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface, SilenceableListenerInterface
{
    use ServiceSubscriberTrait;
    use SilenceableListenerTrait;

    public function __construct(ContainerInterface $locator)
    {
        $this->container = $locator;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $entity = $args->getObject();
        if (!$entity instanceof User) {
            return;
        }
        $userName = $entity->getFirstName();
        $this->markdownConverter()->convert("hello world `$userName`");
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
        ];
    }

    private function markdownConverter(): MarkdownConverterInterface
    {
        return $this->container->get(__METHOD__);
    }
}
