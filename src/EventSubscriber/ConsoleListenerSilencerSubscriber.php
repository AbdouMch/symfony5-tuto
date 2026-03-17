<?php

namespace App\EventSubscriber;

use App\Event\DisableDoctrineListenersEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleListenerSilencerSubscriber implements EventSubscriberInterface
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => 'onCommand'];
    }

    public function onCommand(): void
    {
        $this->dispatcher->dispatch(new DisableDoctrineListenersEvent());
    }
}
