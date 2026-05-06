<?php

namespace App\Tests\Functional\Security;

use App\Entity\ApiToken;
use App\Tests\Functional\AbstractWebTestCase;

class ApiTokenAuthenticatorTest extends AbstractWebTestCase
{
    // Endpoint that requires ROLE_USER (held by every authenticated user)
    private const PROTECTED_URL = '/api/v1/users';

    public function testValidTokenGrantsAccess(): void
    {
        $plainToken = $this->createBearerToken();

        $this->client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer '.$plainToken,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
    }

    public function testMissingTokenHeaderReturns401(): void
    {
        $this->client->request('GET', self::PROTECTED_URL);

        // The api firewall is stateless — no header means no authenticator supports the request,
        // so the firewall returns 401 directly (no session redirect).
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMissingBearerPrefixReturns401(): void
    {
        $plainToken = $this->createBearerToken();

        // Without the "Bearer " prefix, supports() returns false → stateless api firewall → 401.
        $this->client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => $plainToken,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testTokenWithoutDelimiterReturns401(): void
    {
        $this->client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer nodothere',
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseFormatSame('json');
    }

    public function testUnknownIdentifierReturns401(): void
    {
        $this->client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer unknownidentifier.validsecretpart',
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseFormatSame('json');
    }

    public function testWrongSecretReturns401(): void
    {
        $plainToken = $this->createBearerToken();
        [$identifier] = explode(ApiToken::DELIMITER, $plainToken);

        $this->client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer '.$identifier.ApiToken::DELIMITER.'wrongsecret',
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseFormatSame('json');
    }

    public function testSwappedIdentifierAndSecretReturns401(): void
    {
        $plainToken = $this->createBearerToken();
        [$identifier, $secret] = explode(ApiToken::DELIMITER, $plainToken);

        // Reversed: secret used as identifier, identifier used as secret
        $this->client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer '.$secret.ApiToken::DELIMITER.$identifier,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRevokedTokenReturns401(): void
    {
        $plainToken = $this->createBearerToken(['roles' => ['ROLE_ADMIN']]);

        // Revoke the token via the API
        $this->client->request('DELETE', '/api/v1/tokens/'.explode(ApiToken::DELIMITER, $plainToken)[0], [], [], [
            self::AUTH_HEADER => 'Bearer '.$plainToken,
        ]);
        $this->assertResponseStatusCodeSame(204);

        // Attempt to use the revoked token
        $this->client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer '.$plainToken,
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testFailureResponseBodyIsJson(): void
    {
        $this->client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer badidentifier.badsecret',
        ]);

        $this->assertResponseStatusCodeSame(401);

        // The body must decode to valid JSON (non-null).
        // ApiResponse has no @Groups annotations so the serializer returns {}.
        $body = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotNull($body, 'Authentication failure response must be valid JSON');
    }
}
