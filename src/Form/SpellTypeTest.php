<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SpellTypeTest extends AbstractType
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
            ->add('owner', CollectionType::class, [
                'entry_type' => EntityType::class,
                'entry_options' => [
                    'label' => false,
                    'class' => User::class,
                    'choice_value' => 'email',
                ],
                'allow_add' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
