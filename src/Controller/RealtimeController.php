<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\Routing\Annotation\Route;

class RealtimeController extends BaseController
{
    /**
     * @Route("/realtime/subscribe", options={"expose"=true}, name="app_realtime_auth")
     */
    public function apiUser(Request $request, Discovery $discovery, Authorization $authorization): JsonResponse
    {
        $channel = $request->get('topic');
        $user = $this->getUser();
        if (null === $user && 'banner' !== $channel) {
            return $this->json(false, Response::HTTP_UNAUTHORIZED);
        }
        $discovery->addLink($request);
        $authorization->setCookie($request, [$channel]);

        return $this->json(true);
    }
}
