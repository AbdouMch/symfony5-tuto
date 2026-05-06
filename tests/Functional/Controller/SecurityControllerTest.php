<?php

namespace App\Tests\Functional\Controller;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SecurityControllerTest extends WebTestCase
{
    
    use Factories;

    public function testLoginPageRenders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEnable2faRedirectsAnonymousToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/authentication/2fa/enable');

        $this->assertResponseRedirects('/en/login');
    }

    public function testEnable2faIsAccessibleWhenAuthenticated(): void
    {
        $client = static::createClient();
        $client->loginUser(UserFactory::createOne()->object());

        $client->request('GET', '/en/authentication/2fa/enable');

        $this->assertResponseIsSuccessful();
    }
}
