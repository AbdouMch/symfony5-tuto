<?php

namespace App\DataFixtures;

use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $userData = [
            'email' => 'abdou_admin@example.com',
            'firstName' => 'abdou',
        ];
        UserFactory::createOne($userData);
        UserFactory::createMany(10);

        $manager->flush();
    }
}
