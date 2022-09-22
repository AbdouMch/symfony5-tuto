<?php

namespace App\Controller\API;

use App\Controller\BaseController;
use App\Repository\SpellRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * because we use the remember_me cookie.
 *
 * @IsGranted("ROLE_SPELL_READ")
 */
class SpellController extends BaseController
{
    use TranslatableJsonResponseTrait;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/api/spells", name="api_spells_list")
     */
    public function getSpellList(SpellRepository $spellRepository): JsonResponse
    {
        $spells = $spellRepository->findAll();

        return $this->translatedJson($spells, 'name', ['api:spell']);
    }
}
