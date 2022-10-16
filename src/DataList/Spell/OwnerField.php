<?php

namespace App\DataList\Spell;

use App\DataList\AbstractField;
use Doctrine\ORM\QueryBuilder;

class OwnerField extends AbstractField
{
    public function getDefaultFilter(): string
    {
        return 'eq';
    }

    public function getField(): string
    {
        return 'owner.id';
    }

    protected function addJoins(QueryBuilder $qb): QueryBuilder
    {
        $this->innerJoin($this->rootAlias, 'owner', 'owner');

        return $qb;
    }
}
