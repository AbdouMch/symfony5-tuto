<?php

namespace App\Controller\API\V1;

use App\Controller\API\BaseApiController;
use App\DataList\SpellDataList;
use App\Entity\Spell;
use App\Form\Exception\Api\FormValidationException;
use App\Form\SpellTypeTest;
use App\Model\Api\Response as ApiResponse;
use App\Repository\SpellRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @Rest\Route("/spells")
 * @IsGranted("ROLE_SPELL_READ")
 */
class SpellController extends BaseApiController
{
    /**
     * @Rest\Get("", name="spells_list")
     * @Rest\QueryParam(name="name", map=true, nullable=true, description="search by spell name")
     * @Rest\QueryParam(name="constant_code", map=true, nullable=true, description="search by spell constant code")
     * @Rest\QueryParam(name="fields", map=false, nullable=true, description="List of visible fields")
     * @Rest\QueryParam(name="sort", requirements="(asc|desc)", allowBlank=false, default="asc", description="Sort direction")
     */
    public function getSpellList(Request $request, ParamFetcher $paramFetcher, SpellDataList $dataList): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);

        $name = $paramFetcher->get('name');
        $fields = $paramFetcher->get('fields');

        $spells = $dataList->list($limit, $page, 'name', 'ASC');

        return $this->translatedJson(
            $spells,
            'name',
            'spell',
            ['api:spell', 'api:response:list']
        );
    }

    /**
     * @Rest\Post("", name="spell_create")
     */
    public function create(Request $request, SpellRepository $spellRepo): Response
    {
        $form = $this->createForm(SpellTypeTest::class);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Spell $data */
            $spell = $form->getData();
            $spellRepo->add($spell, true);

            return $this->json(
                new ApiResponse($spell, Response::HTTP_CREATED),
                Response::HTTP_CREATED,
                [],
                [
                    AbstractNormalizer::GROUPS => ['api:spell:details', 'api:response', 'api:user'],
                ]
            );
        }

        throw new FormValidationException($form);
    }

    private function getFields(?string $fields)
    {
        if (null === $fields) {
            return [];
        }

        return explode(',', $fields);
    }
}
