<?php

namespace App\Form;

use App\Entity\Question;
use App\Entity\User;
use App\Form\Type\SpellSelectTextType;
use App\Repository\SpellRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
    private SpellRepository $spellRepository;

    public function __construct(UrlGeneratorInterface $urlGenerator, SpellRepository $spellRepository)
    {
        $this->urlGenerator = $urlGenerator;
        $this->spellRepository = $spellRepository;
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
                'rows' => 5, // custom option added by TextAriaSizeExtension
            ])
            ->add('spell', SpellSelectTextType::class, [
                'label' => 'form.spell.label',
                'placeholder' => 'form.spell.placeholder',
                'required' => false,
                'search_field' => $spellSearchField,
                'api_path' => 'api_v1_spells_list',
                'choice_value' => 'name',
                'choice_translation_domain' => 'spell',
                'choice_label' => 'name',
            ]);

//        $this->addToUserField($builder, $options['data'] ?? null);

        if ($options['include_asked_at']) {
            $builder->add('askedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'form.asked_at.label',
                'help' => 'form.asked_at.help',
                'attr' => [
                    'class' => 'js-datepicker',
                ],
                // to give the js datepicker widget the control and force symfony to not add the type date to the input
                'html5' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            'translation_domain' => 'question',
            'mode' => self::WEB_MODE,
            'include_asked_at' => false,
        ]);
    }

    protected function addToUserField(FormBuilderInterface $builder, ?Question $question): void
    {
        $spell = $question ? $question->getSpell() : null;

        if (null === $spell) {
            $builder->remove('toUser');

            return;
        }

        $toUserChoices = $spell->getOwner() ? [$spell->getOwner()] : null;
        $couldEditToUser = null !== $question->getToUser();

        $builder->add('toUser', EntityType::class, [
            'class' => User::class,
            'disabled' => $couldEditToUser,
            'choice_value' => 'email',
            'choice_label' => 'email',
            'label' => 'form.user.label',
            'placeholder' => 'form.user.help',
            'choices' => $toUserChoices,
            'attr' => [
                'class' => 'autocomplete-js',
                'data-autocomplete-url' => $this->urlGenerator->generate('api_v1_users_list', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'data-autocomplete-search-field' => 'email',
                'data-autocomplete-choice-value' => 'email',
                'data-autocomplete-search-operator' => 'startsWith',
                'data-autocomplete-page-size' => 5,
                'data-autocomplete-search-length' => 3,
            ],
        ]);
    }
}
