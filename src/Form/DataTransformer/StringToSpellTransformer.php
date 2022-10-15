<?php

namespace App\Form\DataTransformer;

use App\Entity\Spell;
use App\Repository\SpellRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\TransformationFailedException;

class StringToSpellTransformer implements DataTransformerInterface
{
    private SpellRepository $spellRepository;

    public function __construct(SpellRepository $spellRepository)
    {
        $this->spellRepository = $spellRepository;
    }

    public function transform($value)
    {
        if (null === $value) {
            return '';
        }
        if (!$value instanceof Spell) {
            throw new LogicException('The SpellSelectTextType can only be used with Spell objects');
        }

        return $value->getName();
    }

    public function reverseTransform($value): ?Spell
    {
        // empty value will be handled by a constraint
        if (null === $value) {
            return null;
        }

        $spell = $this->spellRepository->findOneBy([
            'name' => $value,
        ]);

        if (null === $spell) {
            throw new TransformationFailedException(
                sprintf('No spell found with the name "%s"', $value),
                0,
                null,
                'spell.name.not_found',
                ['%name%' => $value]
            );
        }

        return $spell;
    }
}
