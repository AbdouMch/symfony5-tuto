<?php

namespace App\DataList\Spell;

use App\DataList\AbstractDataList;
use App\Entity\Spell;
use Doctrine\ORM\EntityManagerInterface;

class SpellDataList extends AbstractDataList
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Spell::class);
    }

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
