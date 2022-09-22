<?php

namespace App\DataFixtures;

use App\Factory\SpellFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SpellFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        SpellFactory::createOne([
            'name' => 'abraca_dabra',
            'constantCode' => 'spell.abraca_dabra',
        ]);

        SpellFactory::createOne([
            'name' => 'expecto_patronum',
            'constantCode' => 'spell.expecto_patronum',
        ]);

        $manager->flush();
    }
}
