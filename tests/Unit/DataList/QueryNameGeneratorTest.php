<?php

namespace App\Tests\Unit\DataList;

use App\DataList\QueryNameGenerator;
use PHPUnit\Framework\TestCase;

class QueryNameGeneratorTest extends TestCase
{
    private QueryNameGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new QueryNameGenerator();
    }

    public function testGeneratesUniqueNames(): void
    {
        $first = $this->generator->generate('field');
        $second = $this->generator->generate('field');

        $this->assertNotEquals($first, $second);
    }

    public function testReplacesDotsWithUnderscores(): void
    {
        $name = $this->generator->generate('q.name');

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_]+$/', $name);
        $this->assertStringNotContainsString('.', $name);
    }

    public function testCounterIncrementsAcrossDifferentPrefixes(): void
    {
        $a = $this->generator->generate('a');
        $b = $this->generator->generate('b');

        // Both end with different counters
        $this->assertStringEndsWith('_1', $a);
        $this->assertStringEndsWith('_2', $b);
    }
}
