<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Question;
use App\Entity\User;
use App\Security\Voter\QuestionVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class QuestionVoterTest extends TestCase
{
    private QuestionVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new QuestionVoter();
    }

    public function testOwnerIsGrantedEdit(): void
    {
        $owner = new User();
        $question = new Question();
        $question->setOwner($owner);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->tokenFor($owner), $question, [QuestionVoter::EDIT])
        );
    }

    public function testNonOwnerIsDeniedEdit(): void
    {
        $owner = new User();
        $question = new Question();
        $question->setOwner($owner);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->tokenFor(new User()), $question, [QuestionVoter::EDIT])
        );
    }

    public function testAnonymousIsDeniedEdit(): void
    {
        $question = new Question();
        $question->setOwner(new User());

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $question, [QuestionVoter::EDIT])
        );
    }

    public function testAbstainsForNonQuestionSubject(): void
    {
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->tokenFor(new User()), new \stdClass(), [QuestionVoter::EDIT])
        );
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $question = new Question();
        $question->setOwner(new User());

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->tokenFor(new User()), $question, ['DELETE'])
        );
    }

    private function tokenFor(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
