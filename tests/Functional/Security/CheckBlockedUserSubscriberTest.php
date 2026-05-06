<?php

namespace App\Tests\Functional\Security;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CheckBlockedUserSubscriberTest extends WebTestCase
{
    
    use Factories;

    public function testNonBlockedUserCanLogIn(): void
    {
        UserFactory::createOne([
            'email' => 'active@example.com',
            'plainPassword' => 'password',
            'isBlocked' => false,
        ]);

        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $client->submit($crawler->filter('form')->form([
            'email' => 'active@example.com',
            'password' => 'password',
        ]));

        // Successful login redirects away from the login page
        $this->assertResponseRedirects();
        $this->assertStringNotContainsString('/blocked-page', $client->getResponse()->headers->get('Location') ?? '');
    }

    public function testBlockedUserIsRedirectedToBlockedPage(): void
    {
        UserFactory::createOne([
            'email' => 'blocked@example.com',
            'plainPassword' => 'password',
            'isBlocked' => true,
        ]);

        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $client->submit($crawler->filter('form')->form([
            'email' => 'blocked@example.com',
            'password' => 'password',
        ]));

        $this->assertResponseRedirects('/blocked-page');
    }
}
