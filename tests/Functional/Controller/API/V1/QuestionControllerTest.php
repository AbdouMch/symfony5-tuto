<?php

namespace App\Tests\Functional\Controller\API\V1;

use App\Entity\ApiToken;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class QuestionControllerTest extends WebTestCase
{
    
    use Factories;

    private const AUTH_HEADER = 'HTTP_AUTH_TOKEN';
    private const ENDPOINT = '/api/v1/questions';

    private function createBearerToken(array $userAttributes = []): string
    {
        $user = UserFactory::createOne($userAttributes)->object();
        $apiToken = new ApiToken($user);
        $plainToken = $apiToken->getPlainToken();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($apiToken);
        $em->flush();

        return $plainToken;
    }

    public function testCreateRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', self::ENDPOINT);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateWithValidDataReturns201(): void
    {
        $client = static::createClient();
        $bearerToken = $this->createBearerToken();

        $client->request('POST', self::ENDPOINT, [
            'name' => 'How do I cast a fireball spell?',
            'question' => 'I have been trying to learn the fireball spell but nothing works.',
        ], [], [
            self::AUTH_HEADER => 'Bearer ' . $bearerToken,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseFormatSame('json');

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('result', $body);
        $this->assertSame('How do I cast a fireball spell?', $body['result']['name']);
    }

    public function testCreateWithMissingFieldsReturns400(): void
    {
        $client = static::createClient();
        $bearerToken = $this->createBearerToken();

        $client->request('POST', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $bearerToken,
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseFormatSame('json');
    }
}
