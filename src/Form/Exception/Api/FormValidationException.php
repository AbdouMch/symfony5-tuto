<?php

namespace App\Form\Exception\Api;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FormValidationException extends \Exception
{
    protected $message = 'Validation failed';
    protected FormInterface $form;

    public function __construct(FormInterface $form)
    {
        $this->form = $form;

        parent::__construct($this->message);
    }

    public function getResponse(): JsonResponse
    {
        $data = [
            'message' => $this->message,
            'errors' => $this->getErrors($this->form),
        ];

        return new JsonResponse($data, Response::HTTP_BAD_REQUEST);
    }

    protected function getErrors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->all() as $field) {
            $fieldKey = $field->getName();

            if (1 <= \count($field->all())) {
                $fieldErrors = $this->getErrors($field);

                if (!empty($fieldErrors)) {
                    $errors[$fieldKey] = $fieldErrors;
                }
            } else {
                $errors = $this->extractErrors($field, true, $errors);
            }
        }

        return $this->extractErrors($form, false, $errors);
    }

    protected function extractErrors(FormInterface $form, bool $deep, array $errors): array
    {
        foreach ($form->getErrors($deep) as $error) {
            $fieldKey = $error->getOrigin() ? $error->getOrigin()->getName() : null;
            if (\array_key_exists($fieldKey, $errors)) {
                $errors[$fieldKey][] = $error->getMessage();
            } else {
                $errors[$fieldKey] = [$error->getMessage()];
            }
        }

        return $errors;
    }
}
