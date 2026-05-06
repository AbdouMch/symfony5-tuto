<?php

namespace App\Tests\Functional\Controller;

use App\Factory\UserFactory;
use App\Tests\Functional\AbstractWebTestCase;

class SecurityControllerTest extends AbstractWebTestCase
{
    public function testLoginPageRenders(): void
    {
        $this->client->request('GET', '/en/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEnable2faRedirectsAnonymousToLogin(): void
    {
        $this->client->request('GET', '/en/authentication/2fa/enable');
        $this->assertResponseRedirects('http://localhost/en/login');
    }

    public function testEnable2faIsAccessibleWhenAuthenticated(): void
    {
        $this->client->loginUser(UserFactory::createOne()->object());
        $this->client->request('GET', '/en/authentication/2fa/enable');
        $this->assertResponseIsSuccessful();
    }
}
