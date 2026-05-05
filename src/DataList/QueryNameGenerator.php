<?php

namespace App\DataList;

final class QueryNameGenerator
{
    private int $counter = 0;

    public function generate(string $prefix): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $prefix).'_'.++$this->counter;
    }
}
