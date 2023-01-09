<?php

namespace App\Factory;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<User>
 *
 * @method static User|Proxy                     createOne(array $attributes = [])
 * @method static User[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static User|Proxy                     find(object|array|mixed $criteria)
 * @method static User|Proxy                     findOrCreate(array $attributes)
 * @method static User|Proxy                     first(string $sortedField = 'id')
 * @method static User|Proxy                     last(string $sortedField = 'id')
 * @method static User|Proxy                     random(array $attributes = [])
 * @method static User|Proxy                     randomOrCreate(array $attributes = [])
 * @method static User[]|Proxy[]                 all()
 * @method static User[]|Proxy[]                 findBy(array $attributes)
 * @method static User[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static User[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static UserRepository|RepositoryProxy repository()
 * @method        User|Proxy                     create(array|callable $attributes = [])
 */
final class UserFactory extends ModelFactory
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->passwordHasher = $passwordHasher;
    }

    public function withApiToken()
    {
        return $this->afterInstantiate(function (User $user): void {
            $apiToken = new ApiToken($user);
            $user->addApiToken($apiToken);
        });
    }

    protected function getDefaults(): array
    {
        return [
            'email' => self::faker()->email(),
            'firstName' => self::faker()->firstName(),
            'plainPassword' => 'password',
            'isVerified' => true,
        ];
    }

    protected function initialize(): self
    {
        return $this->afterInstantiate(function (User $user): void {
            $plainPassword = $user->getPlainPassword();

            if (null !== $plainPassword) {
                $user
                    ->setPassword(
                        $this->passwordHasher->hashPassword($user, $plainPassword)
                    )
                    ->eraseCredentials();
            }
            $user->agreeTerms();
        });
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
