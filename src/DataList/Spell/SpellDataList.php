<?php

namespace App\DataList\Spell;

use App\DataList\AbstractDataList;

class SpellDataList extends AbstractDataList
{
    protected function getDataFieldsClasses(): array
    {
        return [
            'name' => NameField::class,
            'owner' => OwnerField::class,
        ];
    }

    protected function getRootAlias(): string
    {
        return 'spell';
    }
}
