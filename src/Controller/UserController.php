<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * because we use the remember_me cookie.
 */
class UserController extends BaseController
{
    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     *
     * @Route("/api/user", name="api_user")
     */
    public function apiUser(): JsonResponse
    {
        return $this->json($this->getUser(), Response::HTTP_OK, [], ['groups' => ['api:user']]);
    }

    /**
     * This page is only accessible with a redirect from the login page when the used account is blocked.
     *
     * @Route("/blocked-page", name="app_blocked_page")
     */
    public function blockedUserPage(Request $request): Response
    {
        $redirectUrl = $request->headers->get('referer');
        if ($redirectUrl !== $this->generateUrl('app_login')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('user/blocked_page.html.twig');
    }
}
