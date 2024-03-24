<?php

namespace App\EventSubscriber;

use App\Service\DateTimeService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class KernelRequestSubscriber implements EventSubscriberInterface
{
    private DateTimeService $dateTimeService;

    public function __construct(DateTimeService $dateTimeService)
    {
        $this->dateTimeService = $dateTimeService;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (false === defined('USER_TIMEZONE')) {
            define('USER_TIMEZONE', $this->dateTimeService->getUserTimezone()->getName());
        }
        if (false === defined('USER_LOCAL')) {
            define('USER_LOCAL', $event->getRequest()->getLocale());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest'],
        ];
    }
}
