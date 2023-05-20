<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DateTimeService
{
    public const TIMEZONE_KEY = 'timezone';
    public const TIMEZONE_COOKIE_TTL = '+8 hours';

    private RequestStack $requestStack;
    private ?SessionInterface $session = null;
    private LoggerInterface $logger;

    public function __construct(
        RequestStack $requestStack,
        LoggerInterface $logger
    ) {
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    public function saveTimezoneInSession(\DateTimeZone $timezone): bool
    {
        if (null !== $this->getSession()) {
            $this->session->set(self::TIMEZONE_KEY, $timezone->getName());

            return true;
        }

        return false;
    }

    public function getTimezoneCookie(\DateTimeZone $timeZone): Cookie
    {
        return Cookie::create(
            self::TIMEZONE_KEY,
            $timeZone->getName(),
            self::TIMEZONE_COOKIE_TTL
        );
    }

    public function getUserFriendlyDatetime(\DateTime $dateTime): \DateTime
    {
        return $dateTime->setTimezone($this->getUserTimezone());
    }

    public function getUserTimezone(): \DateTimeZone
    {
        // Session storage
        if (null !== $this->getSession() && null !== ($timezone = $this->session->get(self::TIMEZONE_KEY))) {
            return new \DateTimeZone($timezone);
        }

        // Cookie storage
        if (
            null !== ($request = $this->requestStack->getCurrentRequest())
            && null !== ($timezoneCookie = $request->cookies->get(self::TIMEZONE_KEY))
        ) {
            try {
                return new \DateTimeZone($timezoneCookie);
            } catch (\Throwable $throwable) {
                $this->logger->error('Error when retrieving time zone cookie from request');
            }
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
