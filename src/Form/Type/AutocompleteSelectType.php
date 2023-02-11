<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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
            'data-initial_data' => json_encode([['1' => 'Abra']]),
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
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
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
                'choices' => [],
            ]);
    }

    private function getChoices(FormInterface $form, array $options): array
    {
        $data = $form->getData();
        $selectedChoices = [];
        $dataClass = $options['entity'];

        if (null !== $data) {
            $getter = 'get'.$options['choice_value'];
            $selectedChoices = $this->em->getRepository($dataClass)->findBy([
                $options['choice_value'] => $data->$getter(),
            ]);
        }

        return array_map(static function ($selected) use ($options) {
            $valueGetter = 'get'.$options['choice_value'];
            $labelGetter = 'get'.$options['choice_label'];

            return new ChoiceView($selected, $selected->$valueGetter(), $selected->$labelGetter(), ['selected' => 'selected']);
        }, $selectedChoices
        );
    }
}
