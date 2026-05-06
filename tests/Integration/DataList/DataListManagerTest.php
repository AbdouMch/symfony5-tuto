<?php

namespace App\Tests\Integration\DataList;

use App\DataList\DataListManager;
use App\DataList\Question\QuestionDataListConfiguration;
use App\DataList\Spell\SpellDataListConfiguration;
use App\EventListener\Doctrine\QuestionListener;
use App\Factory\QuestionFactory;
use App\Factory\SpellFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DataListManagerTest extends KernelTestCase
{
    
    use Factories;

    private DataListManager $manager;
    private QuestionDataListConfiguration $questionConfig;
    private SpellDataListConfiguration $spellConfig;

    protected function setUp(): void
    {
        self::bootKernel();

        $listener = self::getContainer()->get(QuestionListener::class);
        $listener->disable();

        $this->manager = self::getContainer()->get(DataListManager::class);
        $this->questionConfig = self::getContainer()->get(QuestionDataListConfiguration::class);
        $this->spellConfig = self::getContainer()->get(SpellDataListConfiguration::class);
    }

    public function testPagination(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createMany(6, ['owner' => $owner, 'question' => 'Content']);

        $result = $this->manager->list($this->questionConfig, ['limit' => 2, 'page' => 1]);

        $this->assertCount(2, $result->getResult());
        $this->assertEquals(6, $result->getTotalCount());
        $this->assertEquals(6, $result->getFilteredCount());
        $this->assertEquals(3, $result->getTotalPages());
    }

    public function testStringFilterContains(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createOne(['name' => 'How to use Symfony?', 'owner' => $owner, 'question' => 'Content']);
        QuestionFactory::createOne(['name' => 'What is PHP?', 'owner' => $owner, 'question' => 'Content']);

        $result = $this->manager->list($this->questionConfig, [
            'title' => ['contains' => 'Symfony'],
        ]);

        $this->assertEquals(1, $result->getFilteredCount());
        $this->assertEquals('How to use Symfony?', $result->getResult()[0]->getName());
    }

    public function testStringFilterIsCaseInsensitive(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createOne(['name' => 'How to use Symfony?', 'owner' => $owner, 'question' => 'Content']);

        $result = $this->manager->list($this->questionConfig, [
            'title' => ['contains' => 'SYMFONY'],
        ]);

        $this->assertEquals(1, $result->getFilteredCount());
    }

    public function testSortingAscending(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createOne(['askedAt' => new \DateTimeImmutable('2023-01-02'), 'owner' => $owner, 'question' => 'Content 1']);
        QuestionFactory::createOne(['askedAt' => new \DateTimeImmutable('2023-01-01'), 'owner' => $owner, 'question' => 'Content 2']);

        $result = $this->manager->list($this->questionConfig, ['sort' => 'asc', 'sort_by' => 'askedAt']);

        $this->assertEquals('2023-01-01', $result->getResult()[0]->getAskedAt()->format('Y-m-d'));
    }

    public function testSortingDescending(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createOne(['askedAt' => new \DateTimeImmutable('2023-01-01'), 'owner' => $owner, 'question' => 'Content 1']);
        QuestionFactory::createOne(['askedAt' => new \DateTimeImmutable('2023-01-02'), 'owner' => $owner, 'question' => 'Content 2']);

        $result = $this->manager->list($this->questionConfig, ['sort' => 'desc', 'sort_by' => 'askedAt']);

        $this->assertEquals('2023-01-02', $result->getResult()[0]->getAskedAt()->format('Y-m-d'));
    }

    public function testFilteredCountDiffersFromTotalWhenFiltered(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createMany(3, ['owner' => $owner, 'question' => 'Content']);
        QuestionFactory::createOne(['name' => 'Unique title XYZ', 'owner' => $owner, 'question' => 'Content']);

        $result = $this->manager->list($this->questionConfig, [
            'title' => ['contains' => 'Unique title XYZ'],
        ]);

        $this->assertEquals(4, $result->getTotalCount());
        $this->assertEquals(1, $result->getFilteredCount());
    }

    public function testJoinFilterByOwner(): void
    {
        $owner1 = UserFactory::createOne();
        $owner2 = UserFactory::createOne();

        SpellFactory::createMany(3, ['owner' => $owner1, 'name' => 'Spell', 'constantCode' => 'CODE']);
        SpellFactory::createMany(2, ['owner' => $owner2, 'name' => 'Spell', 'constantCode' => 'CODE']);

        $result = $this->manager->list($this->spellConfig, [
            'owner' => (string) $owner1->getId(),
        ]);

        $this->assertEquals(3, $result->getFilteredCount());
        $this->assertEquals(5, $result->getTotalCount());
    }

    public function testUnsupportedOperatorThrowsBadRequest(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->manager->list($this->questionConfig, ['title' => ['gt' => 'value']]);
    }
}
