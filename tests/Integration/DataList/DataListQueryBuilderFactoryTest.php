<?php

namespace App\Tests\Integration\DataList;

use App\DataList\DataListQueryBuilderFactory;
use App\DataList\Spell\SpellDataListConfiguration;
use App\Factory\SpellFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DataListQueryBuilderFactoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private DataListQueryBuilderFactory $factory;
    private SpellDataListConfiguration $spellConfig;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->factory     = self::getContainer()->get(DataListQueryBuilderFactory::class);
        $this->spellConfig = self::getContainer()->get(SpellDataListConfiguration::class);
    }

    public function testCreateBaseQueryBuilderReturnsAllEntities(): void
    {
        $owner = UserFactory::createOne();
        SpellFactory::createMany(3, ['owner' => $owner, 'name' => 'Spell', 'constantCode' => 'CODE']);

        $qb     = $this->factory->createBaseQueryBuilder($this->spellConfig);
        $result = $qb->getQuery()->getResult();

        $this->assertCount(3, $result);
    }

    public function testCreateBaseQueryBuilderRespectsCustomSort(): void
    {
        $owner = UserFactory::createOne();
        SpellFactory::createOne(['owner' => $owner, 'name' => 'Bravo', 'constantCode' => 'B']);
        SpellFactory::createOne(['owner' => $owner, 'name' => 'Alpha', 'constantCode' => 'A']);

        $qb     = $this->factory->createBaseQueryBuilder($this->spellConfig, 'name', 'ASC');
        $result = $qb->getQuery()->getResult();

        $this->assertEquals('Alpha', $result[0]->getName());
        $this->assertEquals('Bravo', $result[1]->getName());
    }

    public function testCreateFilteredQueryBuilderAppliesUserFilters(): void
    {
        $owner1 = UserFactory::createOne();
        $owner2 = UserFactory::createOne();
        SpellFactory::createMany(2, ['owner' => $owner1, 'name' => 'Spell', 'constantCode' => 'CODE']);
        SpellFactory::createMany(3, ['owner' => $owner2, 'name' => 'Spell', 'constantCode' => 'CODE']);

        $qb = $this->factory->createFilteredQueryBuilder(
            $this->spellConfig,
            ['owner' => (string) $owner1->getId()]
        );
        $result = $qb->getQuery()->getResult();

        $this->assertCount(2, $result);
    }
}
