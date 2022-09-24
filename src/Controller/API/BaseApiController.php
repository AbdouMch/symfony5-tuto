<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class BaseApiController extends AbstractController
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    protected function translatedJson($data, string $translatableField, string $translationDomain, array $groups = []): JsonResponse
    {
        return $this->json(
            $data,
            200,
            [],
            [
                AbstractNormalizer::GROUPS => $groups,
                AbstractNormalizer::CALLBACKS => [
                    $translatableField => function ($data) use ($translationDomain) {
                        return $this->translator->trans($data, [], $translationDomain);
                    },
                ],
            ]
        );
    }
}
