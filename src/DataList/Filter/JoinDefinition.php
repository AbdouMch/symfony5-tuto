<?php

namespace App\DataList\Filter;

final class JoinDefinition
{
    public string $relation;
    public string $alias;
    public string $type;

    public function __construct(string $relation, string $alias, string $type = 'left')
    {
        $this->relation = $relation;
        $this->alias = $alias;
        $this->type = $type;
    }
}
