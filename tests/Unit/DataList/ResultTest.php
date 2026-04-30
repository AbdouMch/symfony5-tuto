<?php

namespace App\Tests\Unit\DataList;

use App\DataList\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testGetTotalPages(): void
    {
        // 50 items, limit 10 => 5 pages
        $result = new Result([], 10, 1, 100, 50);
        $this->assertEquals(5, $result->getTotalPages());

        // 51 items, limit 10 => 6 pages
        $result = new Result([], 10, 1, 100, 51);
        $this->assertEquals(6, $result->getTotalPages());

        // 0 items, limit 10 => 0 pages
        $result = new Result([], 10, 1, 100, 0);
        $this->assertEquals(0, $result->getTotalPages());
    }

    public function testGetTotalPagesWithZeroLimit(): void
    {
        $result = new Result([], 0, 1, 100, 50);
        $this->assertEquals(1, $result->getTotalPages());
    }

    public function testGetters(): void
    {
        $items = ['a', 'b'];
        $result = new Result($items, 10, 2, 100, 50);

        $this->assertEquals($items, $result->getResult());
        $this->assertEquals(10, $result->getLimit());
        $this->assertEquals(2, $result->getPage());
        $this->assertEquals(100, $result->getTotalCount());
        $this->assertEquals(50, $result->getFilteredCount());
    }
}
