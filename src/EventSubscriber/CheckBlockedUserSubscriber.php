<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Security\Exception\BlockedUserException;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class CheckBlockedUserSubscriber implements EventSubscriberInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (false === $passport->hasBadge(UserBadge::class)) {
            return;
        }
        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);
        $user = $userBadge->getUser();
        if (!$user instanceof User) {
            throw new RuntimeException('Unexpected user type');
        }
        if ($user->isIsBlocked()) {
            throw new BlockedUserException();
        }
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        if (!$event->getException() instanceof BlockedUserException) {
            return;
        }

        $response = new RedirectResponse(
            $this->router->generate('app_blocked_page')
        );

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['onCheckPassport', -10],
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }
}
