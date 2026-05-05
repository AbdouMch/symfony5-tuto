<?php

namespace App\Tests\Integration\DataList;

use App\DataList\ApiToken\ApiTokenDataListConfiguration;
use App\DataList\DataListManager;
use App\Entity\ApiToken;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
        $this->config = self::getContainer()->get(ApiTokenDataListConfiguration::class);
        $this->em = self::getContainer()->get('doctrine')->getManager();
    }

    public function testFilteringByUser(): void
    {
        $user1 = UserFactory::createOne();
        $user2 = UserFactory::createOne();

        $this->em->persist(new ApiToken($user1->object()));
        $this->em->persist(new ApiToken($user2->object()));
        $this->em->flush();

        $result = $this->manager->list($this->config, [
            'user' => (string) $user1->getId(),
        ]);

        $this->assertEquals(1, $result->getFilteredCount());
        $this->assertEquals($user1->getId(), $result->getResult()[0]->getUser()->getId());
    }
}
