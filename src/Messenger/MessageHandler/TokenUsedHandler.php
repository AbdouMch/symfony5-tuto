<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\TokenUsed;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class TokenUsedHandler implements MessageHandlerInterface
{
    private ApiTokenRepository $apiTokenRepository;
    private EntityManagerInterface $em;

    public function __construct(ApiTokenRepository $apiTokenRepository, EntityManagerInterface $em)
    {
        $this->apiTokenRepository = $apiTokenRepository;
        $this->em = $em;
    }

    public function __invoke(TokenUsed $message): void
    {
        $token = $this->apiTokenRepository->findOneByIdentifier($message->getIdentifier());
        if (null === $token) {
            return;
        }

        $token->setLastUsedAt(new \DateTimeImmutable());
        $this->em->flush();
    }
}
