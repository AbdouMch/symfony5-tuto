<?php

namespace App\EventSubscriber;

use App\Entity\User;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class CheckBlockedUserSubscriber implements EventSubscriberInterface
{
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
            throw new CustomUserMessageAuthenticationException('Your account is blocked for some reasons. Please contact the site admin');
        }
    }

    public static function getSubscribedEvents()
    {
        return [
          CheckPassportEvent::class => ['onCheckPassport', -10],
        ];
    }
}
