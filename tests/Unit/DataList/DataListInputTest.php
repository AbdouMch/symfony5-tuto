<?php

namespace App\Tests\Unit\DataList;

use App\DataList\DataListInput;
use PHPUnit\Framework\TestCase;

class DataListInputTest extends TestCase
{
    public function testFromArrayWithDefaults(): void
    {
        $input = DataListInput::fromArray([], ['name', 'createdAt'], 'createdAt');

        $this->assertEquals(23, $input->getLimit());
        $this->assertEquals(1, $input->getPage());
        $this->assertEquals('asc', $input->getSort());
        $this->assertEquals('createdAt', $input->getSortBy());
        $this->assertEmpty($input->getFilters());
    }

    public function testFromArrayWithValidParams(): void
    {
        $params = [
            'limit' => 10,
            'page' => 2,
            'sort' => 'desc',
            'sort_by' => 'name',
            'name' => 'test',
        ];
        $input = DataListInput::fromArray($params, ['name', 'createdAt'], 'createdAt');

        $this->assertEquals(10, $input->getLimit());
        $this->assertEquals(2, $input->getPage());
        $this->assertEquals('desc', $input->getSort());
        $this->assertEquals('name', $input->getSortBy());
        $this->assertEquals(['name' => 'test'], $input->getFilters());
    }

    public function testFromArrayWithInvalidSortByDefaultsToProvidedDefault(): void
    {
        $params = [
            'sort_by' => 'invalid_field',
        ];
        $input = DataListInput::fromArray($params, ['name', 'createdAt'], 'createdAt');

        $this->assertEquals('createdAt', $input->getSortBy());
    }

    public function testFromArrayWhitelistsFilters(): void
    {
        $params = [
            'name' => 'test',
            'malicious_filter' => 'hack',
        ];
        $input = DataListInput::fromArray($params, ['name'], 'name');

        $this->assertEquals(['name' => 'test'], $input->getFilters());
        $this->assertArrayNotHasKey('malicious_filter', $input->getFilters());
    }

    public function testFromArrayFiltersOutEmptyStrings(): void
    {
        $params = [
            'name' => '',
            'status' => 'active',
        ];
        $input = DataListInput::fromArray($params, ['name', 'status'], 'name');

        $this->assertEquals(['status' => 'active'], $input->getFilters());
        $this->assertArrayNotHasKey('name', $input->getFilters());
    }

    public function testFromArrayEnsuresMinimumPageAndLimit(): void
    {
        $params = [
            'limit' => 0,
            'page' => -5,
        ];
        $input = DataListInput::fromArray($params, ['name'], 'name');

        $this->assertEquals(1, $input->getLimit());
        $this->assertEquals(1, $input->getPage());
    }
}
