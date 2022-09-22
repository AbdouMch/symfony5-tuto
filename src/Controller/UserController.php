<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * because we use the remember_me cookie
 * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
 */
class UserController extends BaseController
{
    /**
     * @Route("/api/user", name="api_user")
     */
    public function apiUser(): JsonResponse
    {
        return $this->json($this->getUser(), Response::HTTP_OK, [], ['groups' => ['api:user']]);
    }
}