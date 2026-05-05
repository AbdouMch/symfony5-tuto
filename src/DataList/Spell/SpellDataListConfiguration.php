<?php

namespace App\DataList\Spell;

use App\DataList\DataListConfigurationInterface;
use App\DataList\Filter\FieldDefinition;
use App\DataList\Filter\FilterType;
use App\DataList\Filter\JoinDefinition;
use App\Entity\Spell;

class SpellDataListConfiguration implements DataListConfigurationInterface
{
    public function getEntityClass(): string
    {
        return Spell::class;
    }

    public function getRootAlias(): string
    {
        return 'spell';
    }

    public function getDefaultSortBy(): string
    {
        return 'name';
    }

    public function getFields(): array
    {
        return [
            'name' => new FieldDefinition('spell.name', FilterType::STRING, 'contains'),
            'owner' => new FieldDefinition('spell_owner.id', FilterType::NUMBER, 'eq', [
                new JoinDefinition('spell.owner', 'spell_owner', 'inner'),
            ]),
        ];
    }
}
