<?php

namespace App\Tests\Integration\DataList;

use App\DataList\ApiToken\ApiTokenDataList;
use App\DataList\DataListInput;
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

    private ApiTokenDataList $dataList;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->dataList = self::getContainer()->get(ApiTokenDataList::class);
        $this->em = self::getContainer()->get('doctrine')->getManager();
    }

    public function testFilteringByUser(): void
    {
        $user1 = UserFactory::createOne();
        $user2 = UserFactory::createOne();

        $token1 = new ApiToken($user1->object());
        $token2 = new ApiToken($user2->object());

        $this->em->persist($token1);
        $this->em->persist($token2);
        $this->em->flush();

        // Test filtering by user1
        $input = DataListInput::fromArray([
            'user' => (string) $user1->getId(),
        ], ['user'], 'createdAt');

        $result = $this->dataList->list($input);

        $this->assertEquals(1, $result->getFilteredCount());
        $this->assertEquals($user1->getId(), $result->getResult()[0]->getUser()->getId());
    }
}
