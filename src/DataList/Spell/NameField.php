<?php

namespace App\DataList\Spell;

use App\DataList\AbstractField;
use Doctrine\ORM\QueryBuilder;

class NameField extends AbstractField
{
    public function getDefaultFilter(): string
    {
        return 'contains';
    }

    public function getField(): string
    {
        return $this->rootAlias.'.name';
    }

    protected function addJoins(QueryBuilder $qb): QueryBuilder
    {
        // no joins needed
        return $qb;
    }
}
