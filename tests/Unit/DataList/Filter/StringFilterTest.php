<?php

namespace App\Tests\Unit\DataList\Filter;

use App\DataList\Filter\FilterType;
use App\DataList\Filter\StringFilter;
use App\DataList\QueryNameGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StringFilterTest extends TestCase
{
    private StringFilter $filter;
    private QueryBuilder $qb;
    private QueryNameGenerator $nameGenerator;

    protected function setUp(): void
    {
        $this->filter = new StringFilter();
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
        $this->assertTrue($this->filter->supports('contains'));
        $this->assertTrue($this->filter->supports('startsWith'));
        $this->assertTrue($this->filter->supports('endsWith'));
        $this->assertFalse($this->filter->supports('gt'));
        $this->assertFalse($this->filter->supports('lt'));
        $this->assertFalse($this->filter->supports('gtOrNull'));
    }

    public function testEq(): void
    {
        $this->filter->apply($this->qb, 'e.name', 'eq', 'symfony', $this->nameGenerator);

        $this->assertStringContainsString('e.name = :e_name_1', $this->qb->getDQL());
        $this->assertEquals('symfony', $this->qb->getParameter('e_name_1')->getValue());
    }

    public function testNeq(): void
    {
        $this->filter->apply($this->qb, 'e.name', 'neq', 'symfony', $this->nameGenerator);

        $this->assertStringContainsString('e.name != :e_name_1', $this->qb->getDQL());
        $this->assertEquals('symfony', $this->qb->getParameter('e_name_1')->getValue());
    }

    public function testContains(): void
    {
        $this->filter->apply($this->qb, 'e.name', 'contains', 'Symfony', $this->nameGenerator);

        $this->assertStringContainsString('LOWER(e.name) LIKE :e_name_1', $this->qb->getDQL());
        $this->assertEquals('%symfony%', $this->qb->getParameter('e_name_1')->getValue());
    }

    public function testStartsWith(): void
    {
        $this->filter->apply($this->qb, 'e.name', 'startsWith', 'Sym', $this->nameGenerator);

        $this->assertStringContainsString('LOWER(e.name) LIKE :e_name_1', $this->qb->getDQL());
        $this->assertEquals('sym%', $this->qb->getParameter('e_name_1')->getValue());
    }

    public function testEndsWith(): void
    {
        $this->filter->apply($this->qb, 'e.name', 'endsWith', 'Fony', $this->nameGenerator);

        $this->assertStringContainsString('LOWER(e.name) LIKE :e_name_1', $this->qb->getDQL());
        $this->assertEquals('%fony', $this->qb->getParameter('e_name_1')->getValue());
    }

    public function testUnsupportedOperatorThrows(): void
    {
        $this->expectException(HttpException::class);
        $this->filter->apply($this->qb, 'e.name', 'gt', 'value', $this->nameGenerator);
    }

    public function testMultipleFiltersGenerateUniqueParams(): void
    {
        $this->filter->apply($this->qb, 'e.name', 'contains', 'foo', $this->nameGenerator);
        $this->filter->apply($this->qb, 'e.name', 'contains', 'bar', $this->nameGenerator);

        $this->assertNotNull($this->qb->getParameter('e_name_1'));
        $this->assertNotNull($this->qb->getParameter('e_name_2'));
    }
}
