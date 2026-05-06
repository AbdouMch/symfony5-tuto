<?php

namespace App\Tests\Functional\Controller;

use App\Factory\UserFactory;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RegistrationControllerTest extends WebTestCase
{
    
    use Factories;

    public function testRegisterPageRenders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/register');

        $this->assertResponseIsSuccessful();
    }

    public function testRegisterSubmitCreatesUserAndRedirects(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/register');

        $client->submit($crawler->filter('form')->form([
            'registration_form[email]' => 'newuser@example.com',
            'registration_form[firstName]' => 'Test',
            'registration_form[plainPassword]' => 'Password123!',
            'registration_form[agreeTerms]' => '1',
        ]));

        $this->assertResponseRedirects();

        $userRepo = static::getContainer()->get(UserRepository::class);
        $this->assertNotNull($userRepo->findOneBy(['email' => 'newuser@example.com']));
    }

    public function testRegisterWithDuplicateEmailShowsError(): void
    {
        // Create a user directly in DB to simulate existing account
        UserFactory::createOne(['email' => 'duplicate@example.com']);

        $client = static::createClient();
        $crawler = $client->request('GET', '/en/register');

        $client->submit($crawler->filter('form')->form([
            'registration_form[email]' => 'duplicate@example.com',
            'registration_form[firstName]' => 'Test',
            'registration_form[plainPassword]' => 'Password123!',
            'registration_form[agreeTerms]' => '1',
        ]));

        // Stays on the register page with a validation error
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
