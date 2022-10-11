<?php

namespace App\Form\Exception\Api;

use Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FormValidationException extends Exception
{
    protected $message = "Validation failed";
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
                $fieldErrors = self::getErrors($field);

                if (!empty($fieldErrors)) {
                    $errors[$fieldKey] = $fieldErrors;
                }
            } else {
                foreach ($field->getErrors(true) as $error) {
                    if (\array_key_exists($fieldKey, $errors)) {
                        $errors[$fieldKey][] = $error->getMessage();
                    } else {
                        $errors[$fieldKey] = [$error->getMessage()];
                    }
                }
            }
        }

        return $errors;
    }
}