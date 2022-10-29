<?php

namespace App\Form;

use App\Entity\Question;
use App\Form\Type\SpellSelectTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QuestionFormType extends AbstractType
{
    public const WEB_MODE = 'WEB';
    public const API_MODE = 'API';
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // the spells are searched by name by default
        $spellSearchField = 'name';
        if (self::API_MODE === $options['mode']) {
            // in api we search the spells by id
            $spellSearchField = 'id';
        }

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
            ->add('spell', SpellSelectTextType::class, [
                'required' => false,
                'search_field' => $spellSearchField,
                'multiple' => false,
                'attr' => [
                    'class' => 'autocomplete-js',
                    'data-autocomplete-url' => $this->urlGenerator->generate('api_v1_spells_list', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'data-autocomplete-search-field' => 'name',
                    'data-autocomplete-search-operator' => 'contains',
                ],
            ])
            ->add('user', TextType::class, [
                'label' => 'form.user.label',
                'help' => 'form.user.help',
                'mapped' => false,
                'attr' => [
                    'class' => 'autocomplete-js',
                    'data-autocomplete-url' => $this->urlGenerator->generate('api_v1_users_list', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'data-autocomplete-search-field' => 'email',
                    'data-autocomplete-search-operator' => 'startsWith',
                    'data-autocomplete-page-size' => 5,
                    'data-autocomplete-search-length' => 3,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           'data_class' => Question::class,
            'translation_domain' => 'question',
            'mode' => self::WEB_MODE,
        ]);
    }
}
