<?php

namespace App\Tests\Functional\Security;

use App\Factory\UserFactory;
use App\Tests\Functional\AbstractWebTestCase;

class CheckBlockedUserSubscriberTest extends AbstractWebTestCase
{
    public function testNonBlockedUserCanLogIn(): void
    {
        UserFactory::createOne([
            'email' => 'active@example.com',
            'plainPassword' => 'password',
            'isBlocked' => false,
        ]);

        $crawler = $this->client->request('GET', '/en/login');

        $this->client->submit($crawler->filter('form')->form([
            'email' => 'active@example.com',
            'password' => 'password',
        ]));

        // Successful login redirects away from the login page
        $this->assertResponseRedirects();
        $this->assertStringNotContainsString('/blocked-page', $this->client->getResponse()->headers->get('Location') ?? '');
    }

    public function testBlockedUserIsRedirectedToBlockedPage(): void
    {
        UserFactory::createOne([
            'email' => 'blocked@example.com',
            'plainPassword' => 'password',
            'isBlocked' => true,
        ]);

        $crawler = $this->client->request('GET', '/en/login');

        $this->client->submit($crawler->filter('form')->form([
            'email' => 'blocked@example.com',
            'password' => 'password',
        ]));

        $this->assertResponseRedirects('/en/blocked-page');
    }
}
