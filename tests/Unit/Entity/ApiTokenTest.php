<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ApiToken;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ApiTokenTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);
    }

    public function testConstructorGeneratesValidIdentifier(): void
    {
        $token = new ApiToken($this->user);

        // bin2hex(random_bytes(8)) produces exactly 16 hexadecimal characters
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $token->getIdentifier());
    }

    public function testPlainTokenHasCorrectFormat(): void
    {
        $token = new ApiToken($this->user);
        $plain = $token->getPlainToken();

        $this->assertNotNull($plain);
        $parts = explode(ApiToken::DELIMITER, $plain);
        $this->assertCount(2, $parts, 'Plain token must have exactly one delimiter');
    }

    public function testPlainTokenIdentifierMatchesGetIdentifier(): void
    {
        $token = new ApiToken($this->user);
        [$identifier] = explode(ApiToken::DELIMITER, $token->getPlainToken());

        $this->assertSame($token->getIdentifier(), $identifier);
    }

    public function testPlainTokenSecretIs64HexChars(): void
    {
        $token = new ApiToken($this->user);
        [, $secret] = explode(ApiToken::DELIMITER, $token->getPlainToken());

        // bin2hex(random_bytes(32)) produces exactly 64 hexadecimal characters
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $secret);
    }

    public function testVerifySecretReturnsTrueForCorrectSecret(): void
    {
        $token = new ApiToken($this->user);
        [, $secret] = explode(ApiToken::DELIMITER, $token->getPlainToken());

        $this->assertTrue($token->verifySecret($secret));
    }

    public function testVerifySecretReturnsFalseForWrongSecret(): void
    {
        $token = new ApiToken($this->user);

        $this->assertFalse($token->verifySecret('wrong_secret'));
    }

    public function testVerifySecretReturnsFalseForEmptySecret(): void
    {
        $token = new ApiToken($this->user);

        $this->assertFalse($token->verifySecret(''));
    }

    public function testVerifySecretReturnsFalseForIdentifierUsedAsSecret(): void
    {
        $token = new ApiToken($this->user);

        // Supplying the identifier where the secret is expected must fail
        $this->assertFalse($token->verifySecret($token->getIdentifier()));
    }

    public function testTwoTokensHaveDifferentIdentifiers(): void
    {
        $a = new ApiToken($this->user);
        $b = new ApiToken($this->user);

        $this->assertNotSame($a->getIdentifier(), $b->getIdentifier());
    }

    public function testTwoTokensHaveDifferentSecrets(): void
    {
        $a = new ApiToken($this->user);
        $b = new ApiToken($this->user);

        [, $secretA] = explode(ApiToken::DELIMITER, $a->getPlainToken());
        [, $secretB] = explode(ApiToken::DELIMITER, $b->getPlainToken());

        $this->assertNotSame($secretA, $secretB);
    }

    public function testGetUserReturnsConstructorUser(): void
    {
        $token = new ApiToken($this->user);

        $this->assertSame($this->user, $token->getUser());
    }

    public function testGetIdReturnsNullBeforePersist(): void
    {
        $token = new ApiToken($this->user);

        $this->assertNull($token->getId());
    }
}
