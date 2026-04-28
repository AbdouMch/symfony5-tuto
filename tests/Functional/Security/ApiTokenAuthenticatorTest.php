<?php

namespace App\Tests\Functional\Security;

use App\Entity\ApiToken;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ApiTokenAuthenticatorTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    // Header name from API_TOKEN_HEADER env var ("auth-token" → HTTP_AUTH_TOKEN in test client)
    private const AUTH_HEADER = 'HTTP_AUTH_TOKEN';

    // Endpoint that requires ROLE_USER (held by every authenticated user)
    private const PROTECTED_URL = '/api/v1/users';

    /**
     * Creates a persisted user + ApiToken and returns the plaintext token string.
     */
    private function createToken(array $userAttributes = []): string
    {
        $user = UserFactory::createOne($userAttributes);
        $apiToken = new ApiToken($user->object());
        $plainToken = $apiToken->getPlainToken();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($apiToken);
        $em->flush();

        return $plainToken;
    }

    // --- Happy path ----------------------------------------------------------

    public function testValidTokenGrantsAccess(): void
    {
        $client = static::createClient();
        $plainToken = $this->createToken();

        $client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $plainToken,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
    }

    // --- Missing / malformed token -------------------------------------------

    public function testMissingTokenHeaderReturns401(): void
    {
        $client = static::createClient();

        $client->request('GET', self::PROTECTED_URL);

        // The api firewall is stateless — no header means no authenticator supports the request,
        // so the firewall returns 401 directly (no session redirect).
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMissingBearerPrefixReturns401(): void
    {
        $client = static::createClient();
        $plainToken = $this->createToken();

        // Without the "Bearer " prefix, supports() returns false → stateless api firewall → 401.
        $client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => $plainToken,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testTokenWithoutDelimiterReturns401(): void
    {
        $client = static::createClient();

        $client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer nodothere',
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseFormatSame('json');
    }

    // --- Wrong credentials ---------------------------------------------------

    public function testUnknownIdentifierReturns401(): void
    {
        $client = static::createClient();

        $client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer unknownidentifier.validsecretpart',
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseFormatSame('json');
    }

    public function testWrongSecretReturns401(): void
    {
        $client = static::createClient();
        $plainToken = $this->createToken();
        [$identifier] = explode(ApiToken::DELIMITER, $plainToken);

        $client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $identifier . ApiToken::DELIMITER . 'wrongsecret',
        ]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseFormatSame('json');
    }

    public function testSwappedIdentifierAndSecretReturns401(): void
    {
        $client = static::createClient();
        $plainToken = $this->createToken();
        [$identifier, $secret] = explode(ApiToken::DELIMITER, $plainToken);

        // Reversed: secret used as identifier, identifier used as secret
        $client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $secret . ApiToken::DELIMITER . $identifier,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    // --- Revoked token -------------------------------------------------------

    public function testRevokedTokenReturns401(): void
    {
        $client = static::createClient();
        $plainToken = $this->createToken(['roles' => ['ROLE_ADMIN']]);

        // Revoke the token via the API
        $client->request('DELETE', '/api/v1/tokens/' . explode(ApiToken::DELIMITER, $plainToken)[0], [], [], [
            self::AUTH_HEADER => 'Bearer ' . $plainToken,
        ]);
        $this->assertResponseStatusCodeSame(204);

        // Attempt to use the revoked token
        $client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer ' . $plainToken,
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    // --- Failure response format ---------------------------------------------

    public function testFailureResponseBodyIsJson(): void
    {
        $client = static::createClient();

        $client->request('GET', self::PROTECTED_URL, [], [], [
            self::AUTH_HEADER => 'Bearer badidentifier.badsecret',
        ]);

        $this->assertResponseStatusCodeSame(401);

        // The body must decode to valid JSON (non-null).
        // ApiResponse has no @Groups annotations so the serializer returns {}.
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotNull($body, 'Authentication failure response must be valid JSON');
    }
}
