<?php

namespace App\Controller\API\V2;

use App\Controller\API\BaseApiController;
use App\Entity\Spell;
use App\Form\Exception\Api\FormValidationException;
use App\Form\SpellTypeTest;
use App\Model\Api\Response as ApiResponse;
use App\Repository\SpellRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @Rest\Get("", name="spells_list")
     *
     * @OA\Get(
     *     path="/api/v2/spells",
     *     summary="Get all spells with full details (no pagination)",
     *     description="Returns every spell including owner information. V2 differs from V1 in that it returns all records without pagination and always includes owner details.",
     *     tags={"Spells"},
     *     security={{"ApiToken":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Full spell list",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="array", @OA\Items(ref="#/components/schemas/SpellDetails")),
     *             @OA\Property(property="code", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Not authenticated"),
     *     @OA\Response(response=403, description="Forbidden — requires ROLE_SPELL_READ")
     * )
     */
    public function getSpellList(SpellRepository $spellRepository): JsonResponse
    {
        $spells = $spellRepository->findAll();

        return $this->translatedJson(
            new ApiResponse($spells, Response::HTTP_OK),
            'name',
            'spell',
            ['api:spell:details', 'api:response']
        );
    }

    /**
     * @Rest\Post("", name="spell_create")
     *
     * @Rest\View(serializerGroups={"api:spell:details", "api:response", "api:user"})
     *
     * @OA\Post(
     *     path="/api/v2/spells",
     *     summary="Create a spell (V2)",
     *     tags={"Spells"},
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
    public function create(Request $request): View
    {
        // this is a test controller
        $form = $this->createForm(SpellTypeTest::class);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Spell $data */
            $spell = $form->getData();

            return $this->view(
                new ApiResponse($spell, Response::HTTP_CREATED),
                Response::HTTP_CREATED,
            );
        }

        throw new FormValidationException($form);
    }
}
