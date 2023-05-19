<?php

namespace App\Controller;

use App\Service\DateTimeService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Timezone;
use Symfony\Component\Validator\Validation;

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

    /**
     * @Route("/sync-timezone", name="app_sync_timezone", options={"expose"=true})
     */
    public function syncTimezone(Request $request, DateTimeService $dateTimeService, LoggerInterface $logger): Response
    {
        $data = $request->request->get('timezone');

        if (null !== $data && $request->hasSession()) {
            /** @var string $data */
            $data = filter_var($data, FILTER_SANITIZE_STRING);
            $validator = Validation::createValidator();
            $violations = $validator->validate($data, [new Timezone()]);

            if (0 !== $violations->count()) {
                return $this->json(false);
            }

            try {
                $timezone = new \DateTimeZone($data);
            } catch (\Throwable $exception) {
                $logger->warning("Error when synchronizing timezone: $data");

                return $this->json(false);
            }
            $dateTimeService->saveTimezoneInSession($timezone);
        }

        return $this->json(true);
    }
}
