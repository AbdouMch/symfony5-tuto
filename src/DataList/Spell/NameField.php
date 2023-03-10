<?php

namespace App\DataList\Spell;

use App\DataList\AbstractField;

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
}
