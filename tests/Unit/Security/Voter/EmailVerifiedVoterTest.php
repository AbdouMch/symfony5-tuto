<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\User;
use App\Security\Voter\EmailVerifiedVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EmailVerifiedVoterTest extends TestCase
{
    private EmailVerifiedVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new EmailVerifiedVoter();
    }

    public function testVerifiedUserIsGranted(): void
    {
        $user = new User();
        $user->setIsVerified(true);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->tokenFor($user), null, ['IS_VERIFIED'])
        );
    }

    public function testUnverifiedUserIsDenied(): void
    {
        $user = new User();
        $user->setIsVerified(false);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->tokenFor($user), null, ['IS_VERIFIED'])
        );
    }

    public function testAnonymousIsDenied(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, null, ['IS_VERIFIED'])
        );
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $user = new User();
        $user->setIsVerified(true);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->tokenFor($user), null, ['ROLE_USER'])
        );
    }

    private function tokenFor(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
