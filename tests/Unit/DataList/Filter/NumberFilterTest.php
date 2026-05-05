<?php

namespace App\Tests\Unit\DataList\Filter;

use App\DataList\Filter\NumberFilter;
use App\DataList\QueryNameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NumberFilterTest extends TestCase
{
    private NumberFilter $filter;
    private QueryBuilder $qb;
    private QueryNameGenerator $nameGenerator;

    protected function setUp(): void
    {
        $this->filter = new NumberFilter();
        $this->nameGenerator = new QueryNameGenerator();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getExpressionBuilder')->willReturn(new Expr());
        $this->qb = new QueryBuilder($em);
        $this->qb->from('TestEntity', 'e')->select('e');
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->filter->supports('eq'));
        $this->assertTrue($this->filter->supports('neq'));
        $this->assertTrue($this->filter->supports('lt'));
        $this->assertTrue($this->filter->supports('gt'));
        $this->assertTrue($this->filter->supports('lte'));
        $this->assertTrue($this->filter->supports('gte'));
        $this->assertTrue($this->filter->supports('in'));
        $this->assertFalse($this->filter->supports('contains'));
        $this->assertFalse($this->filter->supports('gtOrNull'));
    }

    public function testEq(): void
    {
        $this->filter->apply($this->qb, 'e.id', 'eq', '42', $this->nameGenerator);

        $this->assertStringContainsString('e.id = :e_id_1', $this->qb->getDQL());
        $this->assertEquals('42', $this->qb->getParameter('e_id_1')->getValue());
    }

    public function testLt(): void
    {
        $this->filter->apply($this->qb, 'e.count', 'lt', '10', $this->nameGenerator);

        $this->assertStringContainsString('e.count < :e_count_1', $this->qb->getDQL());
    }

    public function testGt(): void
    {
        $this->filter->apply($this->qb, 'e.count', 'gt', '5', $this->nameGenerator);

        $this->assertStringContainsString('e.count > :e_count_1', $this->qb->getDQL());
    }

    public function testLte(): void
    {
        $this->filter->apply($this->qb, 'e.count', 'lte', '10', $this->nameGenerator);

        $this->assertStringContainsString('e.count <= :e_count_1', $this->qb->getDQL());
    }

    public function testGte(): void
    {
        $this->filter->apply($this->qb, 'e.count', 'gte', '5', $this->nameGenerator);

        $this->assertStringContainsString('e.count >= :e_count_1', $this->qb->getDQL());
    }

    public function testIn(): void
    {
        $this->filter->apply($this->qb, 'e.id', 'in', '[1,2,3]', $this->nameGenerator);

        $this->assertStringContainsString('e.id IN (:e_id_1)', $this->qb->getDQL());
        $this->assertEquals([1, 2, 3], $this->qb->getParameter('e_id_1')->getValue());
    }

    public function testInWithInvalidJsonThrows(): void
    {
        $this->expectException(HttpException::class);
        $this->filter->apply($this->qb, 'e.id', 'in', 'not-json', $this->nameGenerator);
    }

    public function testInWithNonArrayJsonThrows(): void
    {
        $this->expectException(HttpException::class);
        $this->filter->apply($this->qb, 'e.id', 'in', '"string"', $this->nameGenerator);
    }

    public function testUnsupportedOperatorThrows(): void
    {
        $this->expectException(HttpException::class);
        $this->filter->apply($this->qb, 'e.id', 'contains', 'value', $this->nameGenerator);
    }
}
