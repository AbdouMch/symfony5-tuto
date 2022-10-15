<?php

namespace App\DataFixtures;

use App\Factory\QuestionFactory;
use App\Factory\SpellFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $admin = UserFactory::createOne([
             'email' => 'abdou_admin@example.com',
             'firstName' => 'abdou',
             'roles' => ['ROLE_ADMIN'],
         ]);

        $user = UserFactory::new([
            'email' => 'abdou_user@example.com',
            'firstName' => 'abdou',
        ])
            ->withApiToken()
            ->create();

        UserFactory::createMany(10);

        QuestionFactory::createMany(5, static function () use ($admin) {
            return ['owner' => $admin];
        });

        QuestionFactory::createMany(5, static function () use ($user) {
            return ['owner' => $user];
        });

        SpellFactory::createMany(2, static function () use ($user) {
            return ['owner' => $user];
        });

        $manager->flush();
    }
}
