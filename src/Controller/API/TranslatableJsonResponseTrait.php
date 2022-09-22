<?php

namespace App\Controller\API;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;

trait TranslatableJsonResponseTrait
{
    private TranslatorInterface $translator;

    private function translatedJson($data, string $translatableField, array $groups = []): JsonResponse
    {
        return $this->json(
            $data,
            200,
            [],
            [
                AbstractNormalizer::GROUPS => $groups,
                AbstractNormalizer::CALLBACKS => [
                    $translatableField => function ($data) {
                        return $this->translator->trans($data, [], 'spell');
                    },
                ],
            ]
        );
    }
}
