<?php

namespace App\Tests\Functional\Controller\API\V1;

use App\Entity\ApiToken;
use App\Factory\SpellFactory;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SpellControllerTest extends WebTestCase
{
    
    use Factories;

    private const AUTH_HEADER = 'HTTP_AUTH_TOKEN';
    private const ENDPOINT = '/api/v1/spells';

    private function createBearerToken(): string
    {
        $user = UserFactory::createOne()->object();
        $apiToken = new ApiToken($user);
        $plainToken = $apiToken->getPlainToken();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($apiToken);
        $em->flush();

        return $plainToken;
    }

    public function testListRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', self::ENDPOINT);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testListReturnsJsonWithSpells(): void
    {
        SpellFactory::createMany(3);

        $client = static::createClient();
        $client->request('GET', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $this->createBearerToken(),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('result', $body);
        $this->assertCount(3, $body['result']);
    }

    public function testListReturnsEmptyResultWhenNoSpells(): void
    {
        $client = static::createClient();
        $client->request('GET', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $this->createBearerToken(),
        ]);

        $this->assertResponseIsSuccessful();

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(0, $body['result']);
    }
}
