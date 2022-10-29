<?php

namespace App\Controller\API\V1;

use App\Controller\API\BaseApiController;
use App\DataList\Spell\SpellDataList;
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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @Rest\Route("/spells")
 * @IsGranted("ROLE_SPELL_READ")
 */
class SpellController extends BaseApiController
{
    /**
     * @Route("", name="api_v1_spells_list", methods={"GET"})
     * @Rest\QueryParam(name="name", map=true, nullable=true, description="search by spell name")
     * @Rest\QueryParam(name="constant_code", map=true, nullable=true, description="search by spell constant code")
     * @Rest\QueryParam(name="owner", map=true, nullable=true, description="search by owner id")
     * @Rest\QueryParam(name="sort", requirements="(asc|desc)", allowBlank=false, default="asc", description="Sort direction")
     * @Rest\QueryParam(name="sort_by", requirements="\w+", default="name", description="Sort by field name")
     * @Rest\QueryParam(name="limit", map=false, requirements="\d+", default=23, description="size of the page")
     * @Rest\QueryParam(name="page", map=false, requirements="\d+", default=1, description="page number")
     */
    public function getSpellList(ParamFetcher $paramFetcher, SpellDataList $dataList): JsonResponse
    {
        $spells = $dataList->list($paramFetcher);

        return $this->translatedJson(
            $spells,
            'name',
            'spell',
            ['api:spell', 'api:response:list']
        );
    }

    /**
     * @Route("", name="api_v1_spell_create", methods={"POST"})
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
}
