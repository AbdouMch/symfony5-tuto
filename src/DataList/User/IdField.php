<?php

namespace App\DataList\User;

use App\DataList\AbstractField;

class IdField extends AbstractField
{
    public function getDefaultFilter(): string
    {
        return 'equal';
    }

    public function getField(): string
    {
        return $this->rootAlias.'.id';
    }
}
