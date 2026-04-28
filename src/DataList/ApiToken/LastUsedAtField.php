<?php

namespace App\DataList\ApiToken;

use App\DataList\AbstractField;

class LastUsedAtField extends AbstractField
{
    public function getDefaultFilter(): string
    {
        return 'eq';
    }

    public function getField(): string
    {
        return $this->rootAlias.'.lastUsedAt';
    }
}
