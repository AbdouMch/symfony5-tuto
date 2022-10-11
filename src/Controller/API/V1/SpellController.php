<?php

namespace App\Controller\API\V1;

use App\Controller\API\BaseApiController;
use App\Entity\Spell;
use App\Form\Exception\Api\FormValidationException;
use App\Form\SpellTypeTest;
use App\Model\Api\Response as ApiResponse;
use App\Repository\SpellRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
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
     */
    public function getSpellList(SpellRepository $spellRepository): JsonResponse
    {
        $spells = $spellRepository->findAll();

        return $this->translatedJson(
            new ApiResponse($spells, Response::HTTP_OK),
            'name',
            'spell',
            ['api:spell', 'api:response']
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
}
