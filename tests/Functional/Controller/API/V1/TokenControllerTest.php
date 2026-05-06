<?php

namespace App\Tests\Functional\Controller\API\V1;

use App\Entity\ApiToken;
use App\Tests\Functional\AbstractWebTestCase;

class TokenControllerTest extends AbstractWebTestCase
{
    private const ENDPOINT = '/api/v1/tokens';

    public function testAuthenticatedUserReceives201WithPlainToken(): void
    {
        $bearerToken = $this->createBearerToken();

        $this->client->request('POST', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer '.$bearerToken,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseFormatSame('json');

        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('plain_token', $body);

        // Returned token must match identifier.secret format
        $parts = explode(ApiToken::DELIMITER, $body['plain_token']);
        $this->assertCount(2, $parts);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $parts[0]);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $parts[1]);
    }

    public function testResponseTokenIsUsableForAuthentication(): void
    {
        $bearerToken = $this->createBearerToken();

        // Generate a new token via the endpoint
        $this->client->request('POST', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer '.$bearerToken,
        ]);
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $newToken = $body['plain_token'];

        // Use the newly issued token on a protected endpoint
        $this->client->request('GET', '/api/v1/users', [], [], [
            self::AUTH_HEADER => 'Bearer '.$newToken,
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testUnauthenticatedRequestReturns401(): void
    {
        $this->client->request('POST', self::ENDPOINT);

        // Stateless api firewall — no session redirect, just 401
        $this->assertResponseStatusCodeSame(401);
    }

    public function testInvalidTokenReturns401(): void
    {
        $this->client->request('POST', self::ENDPOINT, [], [], [
            self::AUTH_HEADER => 'Bearer badidentifier.badsecret',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    // --- Token revocation ----------------------------------------------------

    public function testRevokingOwnTokenReturns204(): void
    {
        $bearerToken = $this->createBearerToken();
        $identifier = explode(ApiToken::DELIMITER, $bearerToken)[0];

        $this->client->request('DELETE', self::ENDPOINT.'/'.$identifier, [], [], [
            self::AUTH_HEADER => 'Bearer '.$bearerToken,
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testRevokedTokenCannotBeUsedAgain(): void
    {
        $bearerToken = $this->createBearerToken();
        $identifier = explode(ApiToken::DELIMITER, $bearerToken)[0];

        $this->client->request('DELETE', self::ENDPOINT.'/'.$identifier, [], [], [
            self::AUTH_HEADER => 'Bearer '.$bearerToken,
        ]);
        $this->assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/v1/users', [], [], [
            self::AUTH_HEADER => 'Bearer '.$bearerToken,
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testRevokingNonExistentTokenReturns404(): void
    {
        $bearerToken = $this->createBearerToken();

        $this->client->request('DELETE', self::ENDPOINT.'/doesnotexist00', [], [], [
            self::AUTH_HEADER => 'Bearer '.$bearerToken,
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserCannotRevokeAnotherUsersToken(): void
    {
        // Create two separate users with their own tokens
        $attackerToken = $this->createBearerToken();
        $victimToken = $this->createBearerToken();
        $victimIdentifier = explode(ApiToken::DELIMITER, $victimToken)[0];

        // Attacker tries to revoke victim's token — must get 404 (not 403) to avoid enumeration
        $this->client->request('DELETE', self::ENDPOINT.'/'.$victimIdentifier, [], [], [
            self::AUTH_HEADER => 'Bearer '.$attackerToken,
        ]);

        $this->assertResponseStatusCodeSame(404);

        // Victim's token must still work
        $this->client->request('GET', '/api/v1/users', [], [], [
            self::AUTH_HEADER => 'Bearer '.$victimToken,
        ]);
        $this->assertResponseIsSuccessful();
    }
}
