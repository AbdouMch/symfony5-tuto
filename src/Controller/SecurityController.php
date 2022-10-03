<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends BaseController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_email' => $authenticationUtils->getLastUsername(),
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new RuntimeException('This route should not be reached');
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @Route("/change-password", name="app_change_password")
     */
    public function changPassword(): Response
    {
        return new Response('fake password change page');
    }

    /**
     * @Route("/authentication/2fa/enable", name="app_2fa_enable")
     *  // extra security so the user needs to be authenticated fully and not via a remember_me cookie
     * @IsGranted("ROLE_USER")
     */
    public function enable2fa(
        Request $request,
        TotpAuthenticatorInterface $totpAuthenticator,
        EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $error = null;
        if ('POST' === $request->getMethod()) {
            $code = $request->request->get('code');
            if (null !== $code && $totpAuthenticator->checkCode($user, $code)) {
                $user->setIsTotpEnabled(true);
                $entityManager->flush();
                $this->addFlash('success', 'The 2FA is enabled');

                return $this->redirectToRoute('app_homepage');
            }
            $error = 'Invalid code. Please retry.';
        }

        if (false === $user->isTotpAuthenticationEnabled()) {
            $user->setTotpSecret($totpAuthenticator->generateSecret());

            $entityManager->flush();
        }

        $QRContent = $totpAuthenticator->getQRContent($user);

        return $this->render('security/enable_2fa.html.twig', [
            'qr_content' => $QRContent,
            'error' => $error,
        ]);
    }
}
