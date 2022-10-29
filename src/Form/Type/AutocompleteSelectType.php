<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class AutocompleteSelectType extends AbstractType
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
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
        $view->vars['choices'] = [];
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
            ])
            ->setDefaults([
                'search_operator' => 'contains',
                'page_size' => 5,
                'search_length' => 2,
                'multiple' => false,
                'placeholder' => null,
            ]);
    }
}
