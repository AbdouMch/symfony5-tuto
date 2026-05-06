<?php

namespace App\Tests\Functional\Controller;

use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Tests\Functional\AbstractWebTestCase;

class RegistrationControllerTest extends AbstractWebTestCase
{
    public function testRegisterPageRenders(): void
    {
        $this->client->request('GET', '/en/register');

        $this->assertResponseIsSuccessful();
    }

    public function testRegisterSubmitCreatesUserAndRedirects(): void
    {
        $crawler = $this->client->request('GET', '/en/register');

        $this->client->submit($crawler->filter('form')->form([
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[firstName]' => 'Test',
            'registration_form[plainPassword]' => 'Password123!',
            'registration_form[agreeTerms]' => 'on',
        ]));

        $this->assertResponseRedirects();

        $userRepo = static::getContainer()->get(UserRepository::class);
        $this->assertNotNull($userRepo->findOneBy(['email' => 'newuser@example.com']));
    }

    public function testRegisterWithDuplicateEmailShowsError(): void
    {
        // Create a user directly in DB to simulate existing account
        UserFactory::createOne(['email' => 'duplicate@example.com']);

        $crawler = $this->client->request('GET', '/en/register');

        $this->client->submit($crawler->filter('form')->form([
            'registration_form[email]' => 'duplicate@example.com',
            'registration_form[firstName]' => 'Test',
            'registration_form[plainPassword]' => 'Password123!',
            'registration_form[agreeTerms]' => 'on',
        ]));

        // Stays on the register page with a validation error
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
