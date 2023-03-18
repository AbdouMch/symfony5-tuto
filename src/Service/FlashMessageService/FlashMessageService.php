<?php

namespace App\Service\FlashMessageService;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class FlashMessageService
{
    public const SUCCESS = 'success';
    public const INFO = 'info';
    public const ERROR = 'error';
    public const WARNING = 'warning';

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function add(string $type, ?string $title, string $message): void
    {
        $session = $this->getSession();
        if (null === $session) {
            return;
        }
        $session->getFlashBag()
            ->add(
                $type,
                [
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                ]
            );
    }

    public function getAll(): array
    {
        $session = $this->getSession();
        if (null === $session) {
            return [];
        }
        $aggregatedFlashes = [];
        foreach ($session->getFlashBag()->all() as $type => $messages) {
            foreach ($messages as $message) {
                $aggregatedFlashes[] = [
                    'type' => $messages['type'] ?? $type,
                    'title' => $message['title'] ?? null,
                    'message' => $message['message'] ?? $message,
                ];
            }
        }

        return $aggregatedFlashes;
    }

    private function getSession(): ?Session
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            try {
                return $request->getSession();
            } catch (SessionNotFoundException $exception) {
                return null;
            }
        }

        return null;
    }
}
