<?php

namespace App\Form;

use App\Form\Model\UserRegistrationFormModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.field.email.title',
            ])
            // field guessing will do the job ;)
            ->add('firstName', null, [
                'label' => 'form.field.first_name.title',
            ])
            // See the block charging in the twig template
            ->add('agreeTerms', CheckboxType::class)
            ->add('plainPassword', PasswordType::class, [
                'label' => 'form.field.plain_password.title',
                'attr' => ['autocomplete' => 'new-password'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserRegistrationFormModel::class,
            'translation_domain' => 'registration',
        ]);
    }
}
