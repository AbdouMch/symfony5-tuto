<?php

namespace App\Form\Type;

use App\Form\DataTransformer\StringToSpellTransformer;
use App\Repository\SpellRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpellSelectTextType extends AbstractType
{
    public const SEARCH_FIELD_OPTION = 'search_field';

    private SpellRepository $spellRepository;

    public function __construct(SpellRepository $spellRepository)
    {
        $this->spellRepository = $spellRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new StringToSpellTransformer($this->spellRepository, $options[self::SEARCH_FIELD_OPTION]));
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'invalid_message' => 'question.spell.invalid',
            self::SEARCH_FIELD_OPTION => 'name',
        ]);
    }
}
