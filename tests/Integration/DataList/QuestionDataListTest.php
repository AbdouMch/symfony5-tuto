<?php

namespace App\Tests\Integration\DataList;

use App\DataList\DataListManager;
use App\DataList\Question\QuestionDataListConfiguration;
use App\EventListener\Doctrine\QuestionListener;
use App\Factory\QuestionFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class QuestionDataListTest extends KernelTestCase
{
    
    use Factories;

    private DataListManager $manager;
    private QuestionDataListConfiguration $config;

    protected function setUp(): void
    {
        self::bootKernel();

        $listener = self::getContainer()->get(QuestionListener::class);
        $listener->disable();

        $this->manager = self::getContainer()->get(DataListManager::class);
        $this->config = self::getContainer()->get(QuestionDataListConfiguration::class);
    }

    public function testPagination(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createMany(15, ['owner' => $owner, 'question' => 'Some content']);

        $result = $this->manager->list($this->config, ['limit' => 5, 'page' => 1]);

        $this->assertCount(5, $result->getResult());
        $this->assertEquals(15, $result->getTotalCount());
        $this->assertEquals(3, $result->getTotalPages());
    }

    public function testFilteringByName(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createOne(['name' => 'How to use Symfony?', 'owner' => $owner, 'question' => 'Some content']);
        QuestionFactory::createOne(['name' => 'What is PHP?', 'owner' => $owner, 'question' => 'Some content']);

        $result = $this->manager->list($this->config, [
            'title' => ['contains' => 'Symfony'],
        ]);

        $this->assertEquals(1, $result->getFilteredCount());
        $this->assertEquals('How to use Symfony?', $result->getResult()[0]->getName());
    }

    public function testSorting(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createOne(['askedAt' => new \DateTimeImmutable('2023-01-01'), 'owner' => $owner, 'question' => 'Content 1']);
        QuestionFactory::createOne(['askedAt' => new \DateTimeImmutable('2023-01-02'), 'owner' => $owner, 'question' => 'Content 2']);

        $result = $this->manager->list($this->config, [
            'sort' => 'desc',
            'sort_by' => 'askedAt',
        ]);

        $this->assertEquals('2023-01-02', $result->getResult()[0]->getAskedAt()->format('Y-m-d'));
    }
}
