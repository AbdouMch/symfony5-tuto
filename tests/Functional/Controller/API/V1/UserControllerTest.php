<?php

namespace App\Tests\Functional\Controller\API\V1;

use App\Factory\UserFactory;
use App\Tests\Functional\AbstractWebTestCase;

class UserControllerTest extends AbstractWebTestCase
{
    private const ENDPOINT = '/api/v1/users';

    public function testListRequiresAuthentication(): void
    {
        $this->client->request('GET', self::ENDPOINT);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testListReturnsJsonForAuthenticatedUser(): void
    {
        UserFactory::createMany(2);

        $this->client->request('GET', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer '.$this->createBearerToken(),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('result', $body);
        // 2 created + 1 token owner = 3 total
        $this->assertCount(3, $body['result']);
    }
}
