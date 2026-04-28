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
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @Rest\Route("/spells")
 *
 * @IsGranted("ROLE_SPELL_READ")
 *
 * @OA\Tag(name="Spells")
 */
class SpellController extends BaseApiController
{
    /**
     * @Route("", name="api_v1_spells_list", methods="GET")
     *
     * @Rest\QueryParam(name="name", map=true, nullable=true, description="search by spell name")
     * @Rest\QueryParam(name="constant_code", map=true, nullable=true, description="search by spell constant code")
     * @Rest\QueryParam(name="owner", map=true, nullable=true, description="search by owner id")
     * @Rest\QueryParam(name="sort", requirements="(asc|desc)", allowBlank=false, default="asc", description="Sort direction")
     * @Rest\QueryParam(name="sort_by", requirements="\w+", default="name", description="Sort by field name")
     * @Rest\QueryParam(name="limit", map=false, requirements="\d+", default=23, description="size of the page")
     * @Rest\QueryParam(name="page", map=false, requirements="\d+", default=1, description="page number")
     *
     * @OA\Get(
     *     summary="List spells (paginated)",
     *     security={{"ApiToken":{}}},
     *     @OA\Parameter(name="name[]", in="query", required=false, description="Filter by spell name",
     *         @OA\Schema(type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Parameter(name="constant_code[]", in="query", required=false, description="Filter by constant code",
     *         @OA\Schema(type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Parameter(name="owner[]", in="query", required=false, description="Filter by owner ID",
     *         @OA\Schema(type="array", @OA\Items(type="integer"))
     *     ),
     *     @OA\Parameter(name="sort", in="query", required=false, description="Sort direction",
     *         @OA\Schema(type="string", enum={"asc","desc"}, default="asc")
     *     ),
     *     @OA\Parameter(name="sort_by", in="query", required=false, description="Field to sort by",
     *         @OA\Schema(type="string", default="name")
     *     ),
     *     @OA\Parameter(name="limit", in="query", required=false, description="Page size",
     *         @OA\Schema(type="integer", default=23)
     *     ),
     *     @OA\Parameter(name="page", in="query", required=false, description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated spell list",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="array", @OA\Items(ref="#/components/schemas/SpellRead")),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="limit", type="integer", example=23),
     *             @OA\Property(property="page", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Not authenticated"),
     *     @OA\Response(response=403, description="Forbidden — requires ROLE_SPELL_READ")
     * )
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
     * @Route("", name="api_v1_spell_create", methods="POST")
     *
     * @OA\Post(
     *     summary="Create a spell",
     *     security={{"ApiToken":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"name", "constantCode"},
     *                 @OA\Property(property="name", type="string", description="Spell name"),
     *                 @OA\Property(property="constantCode", type="string", description="Spell constant code")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Spell created",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", ref="#/components/schemas/SpellDetails"),
     *             @OA\Property(property="code", type="integer", example=201)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation failed"),
     *     @OA\Response(response=401, description="Not authenticated"),
     *     @OA\Response(response=403, description="Forbidden — requires ROLE_SPELL_READ")
     * )
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
