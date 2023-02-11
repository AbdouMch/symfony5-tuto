<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class AutocompleteSelectType extends AbstractType
{
    private RouterInterface $router;
    private EntityManagerInterface $em;

    public function __construct(RouterInterface $router, EntityManagerInterface $em)
    {
        $this->router = $router;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['multiple']) {
            $builder->addModelTransformer(new CollectionToArrayTransformer());
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $choices = $this->getChoices($form, $options);

        $attr = $view->vars['attr'];
        $class = isset($attr['class']) ? $attr['class'].' ' : '';
        $class .= 'autocomplete-js mb-2';
        $attr = array_merge($options['attr'], [
            'class' => $class,
            'data-autocomplete-url' => $this->router->generate($options['api_path']),
            'data-autocomplete-search-field' => $options['search_field'],
            'data-autocomplete-choice-value' => $options['choice_value'],
            'data-autocomplete-search-operator' => $options['search_operator'],
            'data-autocomplete-page-size' => $options['page_size'],
            'data-autocomplete-search-length' => $options['search_length'],
        ]);
        $view->vars['attr'] = $attr;
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['placeholder'] = $options['placeholder'];
        $view->vars['preferred_choices'] = [];
        $view->vars['choices'] = $choices;
        $view->vars['choice_label'] = $options['choice_label'];
        $view->vars['choice_translation_domain'] = $options['choice_translation_domain'];
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choiceLoader = function (Options $options) {
            if (null === $options['choices']) {
                $queryBuilder = $options['query_builder'];
                if (null === $queryBuilder) {
                    throw new MissingOptionsException('Option "choices" is null so "query_builder" should not be null.');
                }

                return ChoiceList::loader(
                    $this,
                    new CallbackChoiceLoader(function () use ($queryBuilder) {
                        return $queryBuilder->getQuery()
                            ->getResult();
                    }),
                    $options['entity']
                );
            }

            return null;
        };

        $resolver
            ->setRequired([
                'api_path',
                'search_field',
                'choice_value',
                'choice_translation_domain',
                'choice_label',
                'entity',
            ])
            ->setDefaults([
                'search_operator' => 'contains',
                'page_size' => 5,
                'search_length' => 2,
                'multiple' => false,
                'placeholder' => null,
                'choices' => null,
                'query_builder' => null,
                'choice_loader' => $choiceLoader,
            ]);
    }

    private function getChoices(FormInterface $form, array $options): array
    {
        $data = $form->getData();
        $selectedChoices = [];

        $dataClass = $options['entity'];

        if (null !== $data) {
            $getter = 'get'.$options['choice_value'];
            if (false === $options['multiple']) {
                $selectedChoices = $this->em->getRepository($dataClass)->findBy([
                    $options['choice_value'] => $data->$getter(),
                ]);
            } else {
                $choices = [];
                foreach ($data as $datum) {
                    $choices[] = $this->em->getRepository($dataClass)->findBy([
                        $options['choice_value'] => $datum->$getter(),
                    ]);
                }
                $selectedChoices = array_merge($selectedChoices, ...$choices);
            }
        }

        return array_map(
            static function ($selected) use ($options) {
                $valueGetter = 'get'.$options['choice_value'];
                $labelGetter = 'get'.$options['choice_label'];

                return new ChoiceView($selected, $selected->$valueGetter(), $selected->$labelGetter(), ['selected' => 'selected']);
            }, $selectedChoices
        );
    }
}
