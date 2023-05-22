<?php

namespace App\Model\Mapper;

use App\Entity\Spell;
use App\Model\Spell as SpellModel;
use AutoMapperPlus\AutoMapperPlusBundle\AutoMapperConfiguratorInterface;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;

class SpellMapper implements AutoMapperConfiguratorInterface
{
    public function configure(AutoMapperConfigInterface $config): void
    {
        $config->registerMapping(Spell::class, SpellModel::class)
            ->forMember('ownerName', function (Spell $spell) {
                if (null !== ($owner = $spell->getOwner())) {
                    return $owner->getFirstName();
                }

                return '';
            });
    }
}
