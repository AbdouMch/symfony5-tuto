<?php

namespace App\Form\Type;

use App\Form\DataTransformer\StringToSpellTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SpellSelectTextType extends AbstractType
{
    private StringToSpellTransformer $stringToSpellTransformer;

    public function __construct(StringToSpellTransformer $stringToSpellTransformer)
    {
        $this->stringToSpellTransformer = $stringToSpellTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->stringToSpellTransformer);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
