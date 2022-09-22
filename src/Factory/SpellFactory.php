<?php

namespace App\Factory;

use App\Entity\Spell;
use App\Repository\SpellRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Spell>
 *
 * @method static Spell|Proxy                     createOne(array $attributes = [])
 * @method static Spell[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Spell|Proxy                     find(object|array|mixed $criteria)
 * @method static Spell|Proxy                     findOrCreate(array $attributes)
 * @method static Spell|Proxy                     first(string $sortedField = 'id')
 * @method static Spell|Proxy                     last(string $sortedField = 'id')
 * @method static Spell|Proxy                     random(array $attributes = [])
 * @method static Spell|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Spell[]|Proxy[]                 all()
 * @method static Spell[]|Proxy[]                 findBy(array $attributes)
 * @method static Spell[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Spell[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static SpellRepository|RepositoryProxy repository()
 * @method        Spell|Proxy                     create(array|callable $attributes = [])
 */
final class SpellFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->text(),
            'constantCode' => self::faker()->text(),
        ];
    }

    protected static function getClass(): string
    {
        return Spell::class;
    }
}
