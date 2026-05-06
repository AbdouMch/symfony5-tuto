<?php

namespace App\Tests\Integration\DataList;

use App\DataList\ApiToken\ApiTokenDataListConfiguration;
use App\DataList\DataListManager;
use App\Entity\ApiToken;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ApiTokenDataListTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private DataListManager $manager;
    private ApiTokenDataListConfiguration $config;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->manager = self::getContainer()->get(DataListManager::class);
        $this->em      = self::getContainer()->get('doctrine')->getManager();
    }

    private function loginAs(object $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);
        // Re-fetch the config after the security token is set so getScope() sees the user
        $this->config = self::getContainer()->get(ApiTokenDataListConfiguration::class);
    }

    public function testScopeRestrictsToAuthenticatedUser(): void
    {
        $user1 = UserFactory::createOne();
        $user2 = UserFactory::createOne();

        $this->em->persist(new ApiToken($user1->object()));
        $this->em->persist(new ApiToken($user2->object()));
        $this->em->flush();

        $this->loginAs($user1->object());

        // No user filter passed — scope should enforce it automatically
        $result = $this->manager->list($this->config, []);

        $this->assertEquals(1, $result->getFilteredCount());
        $this->assertEquals($user1->getId(), $result->getResult()[0]->getUser()->getId());
    }

    public function testScopeTotalCountIsAlsoScoped(): void
    {
        $user1 = UserFactory::createOne();
        $user2 = UserFactory::createOne();

        $this->em->persist(new ApiToken($user1->object()));
        $this->em->persist(new ApiToken($user1->object()));
        $this->em->persist(new ApiToken($user2->object()));
        $this->em->flush();

        $this->loginAs($user1->object());

        $result = $this->manager->list($this->config, []);

        // Total count respects scope — user1 has 2 tokens, not 3
        $this->assertEquals(2, $result->getTotalCount());
        $this->assertEquals(2, $result->getFilteredCount());
    }

    public function testFilteringByUser(): void
    {
        $user1 = UserFactory::createOne();
        $user2 = UserFactory::createOne();

        $this->em->persist(new ApiToken($user1->object()));
        $this->em->persist(new ApiToken($user2->object()));
        $this->em->flush();

        $this->loginAs($user1->object());

        // Explicitly passing the user filter still works (scope + filter on same column = still user1's tokens)
        $result = $this->manager->list($this->config, [
            'user' => (string) $user1->getId(),
        ]);

        $this->assertEquals(1, $result->getFilteredCount());
        $this->assertEquals($user1->getId(), $result->getResult()[0]->getUser()->getId());
    }
}
