<?php

namespace App\Tests\Unit\DataList\Filter;

use App\DataList\Filter\DateFilter;
use App\DataList\QueryNameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DateFilterTest extends TestCase
{
    private DateFilter $filter;
    private QueryBuilder $qb;
    private QueryNameGenerator $nameGenerator;

    protected function setUp(): void
    {
        $this->filter = new DateFilter();
        $this->nameGenerator = new QueryNameGenerator();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getExpressionBuilder')->willReturn(new Expr());
        $this->qb = new QueryBuilder($em);
        $this->qb->from('TestEntity', 'e')->select('e');
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->filter->supports('eq'));
        $this->assertTrue($this->filter->supports('lt'));
        $this->assertTrue($this->filter->supports('gt'));
        $this->assertTrue($this->filter->supports('lte'));
        $this->assertTrue($this->filter->supports('gte'));
        $this->assertTrue($this->filter->supports('gtOrNull'));
        $this->assertFalse($this->filter->supports('contains'));
        $this->assertFalse($this->filter->supports('in'));
    }

    public function testEq(): void
    {
        $this->filter->apply($this->qb, 'e.createdAt', 'eq', '2023-01-01', $this->nameGenerator);

        $this->assertStringContainsString('e.createdAt = :e_createdAt_1', $this->qb->getDQL());
        $this->assertEquals('2023-01-01', $this->qb->getParameter('e_createdAt_1')->getValue());
    }

    public function testLt(): void
    {
        $this->filter->apply($this->qb, 'e.createdAt', 'lt', '2023-01-01', $this->nameGenerator);

        $this->assertStringContainsString('e.createdAt < :e_createdAt_1', $this->qb->getDQL());
    }

    public function testGt(): void
    {
        $this->filter->apply($this->qb, 'e.createdAt', 'gt', '2023-01-01', $this->nameGenerator);

        $this->assertStringContainsString('e.createdAt > :e_createdAt_1', $this->qb->getDQL());
    }

    public function testLte(): void
    {
        $this->filter->apply($this->qb, 'e.createdAt', 'lte', '2023-01-01', $this->nameGenerator);

        $this->assertStringContainsString('e.createdAt <= :e_createdAt_1', $this->qb->getDQL());
    }

    public function testGte(): void
    {
        $this->filter->apply($this->qb, 'e.createdAt', 'gte', '2023-01-01', $this->nameGenerator);

        $this->assertStringContainsString('e.createdAt >= :e_createdAt_1', $this->qb->getDQL());
    }

    public function testGtOrNull(): void
    {
        $this->filter->apply($this->qb, 'e.lastUsedAt', 'gtOrNull', '2023-01-01', $this->nameGenerator);

        $dql = $this->qb->getDQL();
        $this->assertStringContainsString('e.lastUsedAt > :e_lastUsedAt_1', $dql);
        $this->assertStringContainsString('e.lastUsedAt IS NULL', $dql);
    }

    public function testUnsupportedOperatorThrows(): void
    {
        $this->expectException(HttpException::class);
        $this->filter->apply($this->qb, 'e.createdAt', 'contains', 'value', $this->nameGenerator);
    }
}
