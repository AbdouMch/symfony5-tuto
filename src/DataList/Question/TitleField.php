<?php

namespace App\DataList\Question;

use App\DataList\AbstractField;

class TitleField extends AbstractField
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
