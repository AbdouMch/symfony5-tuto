<?php

namespace App\Form;

use App\Entity\Spell;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SpellType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('constantCode', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_value' => 'email',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Spell::class,
            'csrf_protection' => false,
        ]);
    }
}
