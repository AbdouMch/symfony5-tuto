<?php

namespace App\Tests\Functional;

use App\Entity\ApiToken;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;

abstract class AbstractWebTestCase extends WebTestCase
{
    use Factories;
    // Header name from API_TOKEN_HEADER env var ("auth-token" → HTTP_AUTH_TOKEN in test client)
    protected const AUTH_HEADER = 'HTTP_AUTH_TOKEN';

    protected ?KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient([], [
            'HTTP_ACCEPT' => 'text/html',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
    }

    /**
     * Creates a persisted user + ApiToken and returns the plaintext token string.
     */
    protected function createBearerToken(): string
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
}
