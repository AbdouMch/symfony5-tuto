<?php

namespace App\Controller\API;

use App\Model\ApiResponse;
use App\Repository\SpellRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_SPELL_READ")
 */
class SpellController extends BaseApiController
{
    /**
     * @Route("/api/spells", name="api_spells_list")
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
}
