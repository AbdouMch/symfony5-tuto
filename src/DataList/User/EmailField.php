<?php

namespace App\DataList\User;

use App\DataList\AbstractField;

class EmailField extends AbstractField
{
    public function getDefaultFilter(): string
    {
        return 'contains';
    }

    public function getField(): string
    {
        return $this->rootAlias.'.email';
    }
}
