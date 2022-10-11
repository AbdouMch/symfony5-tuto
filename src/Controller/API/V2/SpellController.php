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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/spells")
 *
 * @IsGranted("ROLE_SPELL_READ")
 */
class SpellController extends BaseApiController
{
    /**
     * @Rest\Get("", name="spells_list")
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
     * @Rest\View(serializerGroups={"api:spell:details", "api:response", "api:user"})
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
