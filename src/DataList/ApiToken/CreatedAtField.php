<?php

namespace App\DataList\ApiToken;

use App\DataList\AbstractField;

class CreatedAtField extends AbstractField
{
    public function getDefaultFilter(): string
    {
        return 'eq';
    }

    public function getField(): string
    {
        return $this->rootAlias.'.createdAt';
    }
}
