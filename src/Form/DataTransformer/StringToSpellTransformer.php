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
    private string $searchField;

    public function __construct(SpellRepository $spellRepository, string $searchField)
    {
        $this->spellRepository = $spellRepository;
        $this->searchField = $searchField;
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
            $this->searchField => $value,
        ]);

        if (null === $spell) {
            throw new TransformationFailedException(sprintf('No spell found with the "%s" "%s"', $this->searchField, $value), 0, null, 'spell.not_found', ['%field%' => $this->searchField, '%name%' => $value]);
        }

        return $spell;
    }
}
