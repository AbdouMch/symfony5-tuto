<?php

namespace App\Tests\Functional\Controller;

use App\EventListener\Doctrine\QuestionListener;
use App\Factory\QuestionFactory;
use App\Factory\UserFactory;
use App\Tests\Functional\AbstractWebTestCase;

class QuestionControllerTest extends AbstractWebTestCase
{
    public function testHomepageIsPublic(): void
    {
        $this->client->request('GET', '/en/');

        $this->assertResponseIsSuccessful();
    }

    public function testShowIsPublic(): void
    {
        static::getContainer()->get(QuestionListener::class)->disable();

        $question = QuestionFactory::createOne();
        $this->client->request('GET', '/en/question/' . $question->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testCreateRedirectsAnonymousToLogin(): void
    {
        
        $this->client->request('GET', '/en/question/create');

        $this->assertResponseRedirects('/en/login');
    }

    public function testCreateIsForbiddenForUnverifiedUser(): void
    {
        
        $this->client->loginUser(UserFactory::createOne(['isVerified' => false])->object());

        $this->client->request('GET', '/en/question/create');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateIsAccessibleForVerifiedUser(): void
    {
        
        $this->client->loginUser(UserFactory::createOne(['isVerified' => true])->object());

        $this->client->request('GET', '/en/question/create');

        $this->assertResponseIsSuccessful();
    }

    public function testEditIsForbiddenForNonOwner(): void
    {
        
        static::getContainer()->get(QuestionListener::class)->disable();

        $owner = UserFactory::createOne()->object();
        $question = QuestionFactory::createOne(['owner' => $owner]);
        $otherUser = UserFactory::createOne()->object();

        $this->client->loginUser($otherUser);
        $this->client->request('GET', '/en/question/' . $question->getId() . '/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditIsAccessibleForOwner(): void
    {
        
        static::getContainer()->get(QuestionListener::class)->disable();

        $owner = UserFactory::createOne()->object();
        $question = QuestionFactory::createOne(['owner' => $owner]);

        $this->client->loginUser($owner);
        $this->client->request('GET', '/en/question/' . $question->getId() . '/edit');

        $this->assertResponseIsSuccessful();
    }
}
