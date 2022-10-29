<?php

namespace App\Form\Type;

use App\Form\DataTransformer\StringToSpellTransformer;
use App\Repository\SpellRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpellSelectTextType extends AbstractType
{
    private SpellRepository $spellRepository;
    private string $projectDir;

    public function __construct(SpellRepository $spellRepository, string $projectDir)
    {
        $this->spellRepository = $spellRepository;
        $this->projectDir = $projectDir;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new StringToSpellTransformer($this->spellRepository, $options['search_field'], $this->projectDir));
    }

    public function getParent(): string
    {
        return AutocompleteSelectType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'invalid_message' => 'question.spell.invalid',
            'search_field' => 'name',
        ]);
    }
}
