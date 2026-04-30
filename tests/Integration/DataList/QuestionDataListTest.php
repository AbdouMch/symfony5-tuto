<?php

namespace App\Tests\Integration\DataList;

use App\DataList\DataListInput;
use App\DataList\Question\QuestionDataList;
use App\EventListener\Doctrine\QuestionListener;
use App\Factory\QuestionFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class QuestionDataListTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private QuestionDataList $dataList;

    protected function setUp(): void
    {
        self::bootKernel();

        // Silence Mercure updates
        $listener = self::getContainer()->get(QuestionListener::class);
        $listener->disable();

        $this->dataList = self::getContainer()->get(QuestionDataList::class);
    }

    public function testPagination(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createMany(15, ['owner' => $owner, 'question' => 'Some content']);

        $input = DataListInput::fromArray(['limit' => 5, 'page' => 1], ['title'], 'askedAt');
        $result = $this->dataList->list($input);

        $this->assertCount(5, $result->getResult());
        $this->assertEquals(15, $result->getTotalCount());
        $this->assertEquals(3, $result->getTotalPages());
    }

    public function testFilteringByName(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createOne(['name' => 'How to use Symfony?', 'owner' => $owner, 'question' => 'Some content']);
        QuestionFactory::createOne(['name' => 'What is PHP?', 'owner' => $owner, 'question' => 'Some content']);

        $input = DataListInput::fromArray([
            'title' => ['contains' => 'Symfony'],
        ], ['title'], 'askedAt');

        $result = $this->dataList->list($input);

        $this->assertEquals(1, $result->getFilteredCount());
        $this->assertEquals('How to use Symfony?', $result->getResult()[0]->getName());
    }

    public function testSorting(): void
    {
        $owner = UserFactory::createOne();
        QuestionFactory::createOne(['askedAt' => new \DateTimeImmutable('2023-01-01'), 'owner' => $owner, 'question' => 'Content 1']);
        QuestionFactory::createOne(['askedAt' => new \DateTimeImmutable('2023-01-02'), 'owner' => $owner, 'question' => 'Content 2']);

        $input = DataListInput::fromArray([
            'sort' => 'desc',
            'sort_by' => 'askedAt',
        ], ['askedAt'], 'askedAt');

        $result = $this->dataList->list($input);

        $this->assertEquals('2023-01-02', $result->getResult()[0]->getAskedAt()->format('Y-m-d'));
    }
}
