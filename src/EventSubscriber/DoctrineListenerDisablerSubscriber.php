<?php

namespace App\EventSubscriber;

use App\Contract\SilenceableListenerInterface;
use App\Event\DisableDoctrineListenersEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DoctrineListenerDisablerSubscriber implements EventSubscriberInterface
{
    /** @var iterable<SilenceableListenerInterface> */
    private iterable $listeners;

    /** @param iterable<SilenceableListenerInterface> $listeners */
    public function __construct(iterable $listeners)
    {
        $this->listeners = $listeners;
    }

    public static function getSubscribedEvents(): array
    {
        return [DisableDoctrineListenersEvent::class => 'onDisable'];
    }

    public function onDisable(): void
    {
        foreach ($this->listeners as $listener) {
            $listener->disable();
        }
    }
}
