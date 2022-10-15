<?php

namespace App\Form;

use App\Entity\Question;
use App\Entity\Spell;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuestionFormType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.name.label',
                'help' => 'form.name.help',
            ])
            ->add('question', TextareaType::class, [
                'label' => 'form.question.label',
                'help' => 'form.question.help',
            ])
            ->add('askedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'form.asked_at.label',
                'help' => 'form.asked_at.help',
                'attr' => [
                    'class' => 'js-datepicker',
                ],
                // to give the js datepicker widget the control and force symfony to not add the type date to the input
                'html5' => false,
            ])
            ->add('spell', EntityType::class, [
                'class' => Spell::class,
                'choice_label' => 'name',
                'choice_translation_domain' => 'spell',
                'label' => 'form.spell.label',
                'placeholder' => 'form.spell.placeholder',
                'required' => false,
                'invalid_message' => 'question.spell.invalid',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
           'data_class' => Question::class,
            'translation_domain' => 'question',
        ]);
    }
}
