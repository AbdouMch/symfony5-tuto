<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DateTimeService
{
    public const TIMEZONE_KEY = 'timezone';

    private RequestStack $requestStack;
    private ?SessionInterface $session = null;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function saveTimezoneInSession(\DateTimeZone $timezone): bool
    {
        if (null !== $this->getSession()) {
            $this->session->set(self::TIMEZONE_KEY, $timezone->getName());

            return true;
        }

        return false;
    }

    public function getUserFriendlyDatetime(\DateTime $dateTime): \DateTime
    {
        return $dateTime->setTimezone($this->getUserTimezone());
    }

    public function getUserTimezone(): \DateTimeZone
    {
        if (null !== $this->getSession() && null !== ($timezone = $this->session->get(self::TIMEZONE_KEY))) {
            return new \DateTimeZone($timezone);
        }

        return new \DateTimeZone(date_default_timezone_get());
    }

    private function getSession(): ?SessionInterface
    {
        if (null !== $this->session) {
            return $this->session;
        }

        try {
            $this->session = $this->requestStack->getSession();

            return $this->session;
        } catch (SessionNotFoundException $exception) {
            return null;
        }
    }
}
