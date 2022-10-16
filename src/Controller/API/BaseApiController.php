<?php

namespace App\Controller\API;

use App\DataList\AbstractDataList;
use App\Entity\User;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method User getUser()
 */
abstract class BaseApiController extends AbstractFOSRestController
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

    protected function getFilters(ParamFetcher $paramFetcher, AbstractDataList $dataList): array
    {
        $filters = [];
        $params = $paramFetcher->all();
        foreach ($dataList->getFields() as $field) {
            if (isset($params[$field])) {
                $filters[$field] = $params[$field];
            }
        }

        return $filters;
    }
}
