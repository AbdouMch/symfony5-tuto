<?php

namespace App\Exporter;

use App\Entity\User;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class QuestionExportLimiter
{
    private RateLimiterFactory $rateLimiterFactory;

    public function __construct(RateLimiterFactory $questionExportLimiter)
    {
        $this->rateLimiterFactory = $questionExportLimiter;
    }

    public function getLimiter(User $user): LimiterInterface
    {
        return $this->rateLimiterFactory->create((string) $user->getId());
    }

    public function reset(User $user): void
    {
        $this->getLimiter($user)->reset();
    }
}
