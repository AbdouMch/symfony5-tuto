<?php

namespace App\Form;

use App\DataList\Spell\SpellDataList;
use App\DataList\User\UserDataList;
use App\Entity\Question;
use App\Entity\Spell;
use App\Entity\User;
use App\Form\Type\AutocompleteSelectType;
use App\Service\DateTimeService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class QuestionFormType extends AbstractType
{
    public const WEB_MODE = 'WEB';
    public const API_MODE = 'API';
    private UserDataList $userDataList;
    private Security $security;
    private SpellDataList $spellDataList;
    private DateTimeService $dateTimeService;

    public function __construct(
        UserDataList $userDataList,
        SpellDataList $spellDataList,
        Security $security,
        DateTimeService $dateTimeService
    ) {
        $this->userDataList = $userDataList;
        $this->security = $security;
        $this->spellDataList = $spellDataList;
        $this->dateTimeService = $dateTimeService;
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
            ->add('version', HiddenType::class)
            ->add('name', TextType::class, [
                'label' => 'form.name.label',
                'help' => 'form.name.help',
            ])
            ->add('question', TextareaType::class, [
                'label' => 'form.question.label',
                'help' => 'form.question.help',
                'rows' => 5, // custom option added by TextAriaSizeExtension
            ])
            ->add('spell', AutocompleteSelectType::class, [
                'entity' => Spell::class,
                'label' => 'form.spell.label',
                'placeholder' => 'form.spell.placeholder',
                'required' => false,
                'search_field' => $spellSearchField,
                'api_path' => 'api_v1_spells_list',
                'choice_value' => 'id',
                'choice_translation_domain' => 'spell',
                'choice_label' => 'name',
                'query_builder' => $this->spellDataList->getQueryBuilder([], 'name', 'ASC'),
            ]);

        $this->addToUserField($builder, $builder->getData());

        if ($options['include_asked_at']) {
            $userTimezone = $this->dateTimeService->getUserTimezone()->getName();

            $builder->add('askedAt', DateTimeType::class, [
                'widget' => 'single_text',
                'view_timezone' => $userTimezone,
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
        $spell = null !== $question ? $question->getSpell() : null;

        if (null === $spell) {
            $builder->remove('toUsers');

            return;
        }

        $userId = $this->security->getUser()->getId();

        $builder->add('toUsers', AutocompleteSelectType::class, [
            'entity' => User::class,
            'multiple' => true,
            'required' => false,
            'choice_value' => 'id',
            'choice_label' => 'email',
            'label' => 'form.user.label',
            'placeholder' => 'form.user.help',
            'api_path' => 'api_v1_users_list',
            'api_parameters' => ['id' => ['neq' => $userId]],
            'search_field' => 'email',
            'query_builder' => $this->userDataList->getQueryBuilder([], 'id', 'ASC')
                ->andWhere("user.id != ($userId)"),
        ]);
    }
}
