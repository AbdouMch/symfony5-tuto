<?php

namespace App\Controller;

use App\DataList\DataListManager;
use App\DataList\Spell\SpellDataListConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;

class SpellController extends AbstractController
{
    /**
     * @Route("/spells/filter", name="app_spell_filter")
     */
    public function filterSpells(Request $request, DataListManager $manager, SpellDataListConfiguration $config, TranslatorInterface $translator): JsonResponse
    {
        $spells = $manager->list($config, $request->query->all());

        return $this->json(
            $spells,
            200,
            [],
            [
                AbstractNormalizer::GROUPS => ['api:spell', 'api:response:list'],
                AbstractNormalizer::CALLBACKS => [
                    'name' => function ($data) use ($translator) {
                        return $translator->trans($data, [], 'spell');
                    },
                ],
            ]
        );
    }
}