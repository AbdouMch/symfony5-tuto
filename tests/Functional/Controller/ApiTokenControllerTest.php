<?php

namespace App\Tests\Functional\Controller;

use App\Entity\ApiToken;
use App\Factory\UserFactory;
use App\Repository\ApiTokenRepository;
use App\Tests\Functional\AbstractWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ApiTokenControllerTest extends AbstractWebTestCase
{
    public function testListRedirectsAnonymousToLogin(): void
    {
        $this->client->request('GET', '/en/profile/tokens');

        $this->assertResponseRedirects('http://localhost/en/login');
    }

    public function testListIsAccessibleWhenAuthenticated(): void
    {
        $this->client->loginUser(UserFactory::createOne()->object());

        $this->client->request('GET', '/en/profile/tokens');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateGeneratesTokenAndRedirects(): void
    {
        $user = UserFactory::createOne()->object();

        $this->client->loginUser($user);

        $this->client->request('POST', '/en/profile/tokens/new');

        $this->client->getResponse();

        $this->assertResponseRedirects('/en/profile/tokens');

        $repo = static::getContainer()->get(ApiTokenRepository::class);
        $this->assertCount(1, $repo->findBy(['user' => $user]));
    }

    public function testDeleteWithValidCsrfRemovesToken(): void
    {
        $user = UserFactory::createOne()->object();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $token = new ApiToken($user);
        $identifier = $token->getIdentifier();
        $em->persist($token);
        $em->flush();


        $this->client->loginUser($user);

        $csrfToken = static::getContainer()
            ->get('security.csrf.token_manager')
            ->getToken('delete_api_token_' . $identifier)
            ->getValue();

        $this->client->request('POST', '/en/profile/tokens/' . $identifier . '/delete', [
            '_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/en/profile/tokens');

        $repo = static::getContainer()->get(ApiTokenRepository::class);
        $this->assertNull($repo->findOneBy(['identifier' => $identifier]));
    }

    public function testDeleteWithInvalidCsrfReturnsForbidden(): void
    {
        $user = UserFactory::createOne()->object();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();
        $token = new ApiToken($user);
        $identifier = $token->getIdentifier();
        $em->persist($token);
        $em->flush();

        $this->client->loginUser($user);

        $this->client->request('POST', '/en/profile/tokens/' . $identifier . '/delete', [
            '_token' => 'invalid-csrf-token',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
