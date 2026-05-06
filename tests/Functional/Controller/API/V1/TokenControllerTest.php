<?php

namespace App\Tests\Functional\Controller\API\V1;

use App\Entity\ApiToken;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TokenControllerTest extends WebTestCase
{
    use Factories;

    private const AUTH_HEADER = 'HTTP_AUTH_TOKEN';
    private const ENDPOINT = '/api/v1/tokens';

    private function createBearerToken(array $userAttributes = []): string
    {
        $user = UserFactory::createOne(array_merge(['roles' => ['ROLE_ADMIN']], $userAttributes));
        $apiToken = new ApiToken($user->object());
        $plainToken = $apiToken->getPlainToken();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($apiToken);
        $em->flush();

        return $plainToken;
    }

    // --- Happy path ----------------------------------------------------------

    public function testAuthenticatedUserReceives201WithPlainToken(): void
    {
        $client = static::createClient();
        $bearerToken = $this->createBearerToken();

        $client->request('POST', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $bearerToken,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseFormatSame('json');

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('plain_token', $body);

        // Returned token must match identifier.secret format
        $parts = explode(ApiToken::DELIMITER, $body['plain_token']);
        $this->assertCount(2, $parts);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $parts[0]);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $parts[1]);
    }

    public function testResponseTokenIsUsableForAuthentication(): void
    {
        $client = static::createClient();
        $bearerToken = $this->createBearerToken();

        // Generate a new token via the endpoint
        $client->request('POST', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $bearerToken,
        ]);
        $body = json_decode($client->getResponse()->getContent(), true);
        $newToken = $body['plain_token'];

        // Use the newly issued token on a protected endpoint
        $client->request('GET', '/api/v1/users', [], [], [
            self::AUTH_HEADER => 'Bearer ' . $newToken,
        ]);
        $this->assertResponseIsSuccessful();
    }

    // --- Unauthenticated access ----------------------------------------------

    public function testUnauthenticatedRequestReturns401(): void
    {
        $client = static::createClient();

        $client->request('POST', self::ENDPOINT);

        // Stateless api firewall — no session redirect, just 401
        $this->assertResponseStatusCodeSame(401);
    }

    public function testInvalidTokenReturns401(): void
    {
        $client = static::createClient();

        $client->request('POST', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer badidentifier.badsecret',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    // --- Token revocation ----------------------------------------------------

    public function testRevokingOwnTokenReturns204(): void
    {
        $client = static::createClient();
        $bearerToken = $this->createBearerToken();
        $identifier = explode(ApiToken::DELIMITER, $bearerToken)[0];

        $client->request('DELETE', self::ENDPOINT . '/' . $identifier, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $bearerToken,
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testRevokedTokenCannotBeUsedAgain(): void
    {
        $client = static::createClient();
        $bearerToken = $this->createBearerToken();
        $identifier = explode(ApiToken::DELIMITER, $bearerToken)[0];

        $client->request('DELETE', self::ENDPOINT . '/' . $identifier, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $bearerToken,
        ]);
        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/v1/users', [], [], [
            self::AUTH_HEADER => 'Bearer ' . $bearerToken,
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testRevokingNonExistentTokenReturns404(): void
    {
        $client = static::createClient();
        $bearerToken = $this->createBearerToken();

        $client->request('DELETE', self::ENDPOINT . '/doesnotexist00', [], [], [
            self::AUTH_HEADER => 'Bearer ' . $bearerToken,
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserCannotRevokeAnotherUsersToken(): void
    {
        $client = static::createClient();

        // Create two separate users with their own tokens
        $attackerToken = $this->createBearerToken();
        $victimToken = $this->createBearerToken();
        $victimIdentifier = explode(ApiToken::DELIMITER, $victimToken)[0];

        // Attacker tries to revoke victim's token — must get 404 (not 403) to avoid enumeration
        $client->request('DELETE', self::ENDPOINT . '/' . $victimIdentifier, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $attackerToken,
        ]);

        $this->assertResponseStatusCodeSame(404);

        // Victim's token must still work
        $client->request('GET', '/api/v1/users', [], [], [
            self::AUTH_HEADER => 'Bearer ' . $victimToken,
        ]);
        $this->assertResponseIsSuccessful();
    }
}