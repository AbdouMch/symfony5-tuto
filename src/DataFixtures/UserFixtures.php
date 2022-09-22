<?php

namespace App\DataFixtures;

use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne([
            'email' => 'abdou_admin@example.com',
            'firstName' => 'abdou',
            'roles' => ['ROLE_ADMIN'],
        ]);
        UserFactory::createOne([
            'email' => 'abdou_user@example.com',
            'firstName' => 'abdou',
        ]);
        UserFactory::createMany(10);

        $manager->flush();
    }
}
