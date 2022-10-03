<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\MailSender\ConfirmationEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        UserAuthenticatorInterface $userAuthenticator,
        FormLoginAuthenticator $formLoginAuthenticator,
        VerifyEmailHelperInterface $verifyEmailHelper,
        ConfirmationEmailSender $confirmationEmailSender
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $userRepository->add($user, true);
            $signatureComponents = $verifyEmailHelper->generateSignature(
                'app_verify_email',
                $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            );
            $confirmationEmailSender->sendConfirmationEmail($user, $signatureComponents->getSignedUrl());

            return $userAuthenticator->authenticateUser(
                $user,
                $formLoginAuthenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify", name="app_verify_email")
     */
    public function verifyUserEmail(
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $userRepository->find($request->query->get('id'));

        if (null === $user) {
            throw $this->createNotFoundException();
        }

        try {
            $verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                $user->getId(),
                $user->getEmail()
            );
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $exception->getReason());

            return $this->redirectToRoute('app_homepage');
        }

        $user->setIsVerified(true);
        $entityManager->flush();

        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->addFlash('success', 'Your account is verified. Enjoy !!');
        } else {
            $this->addFlash('success', 'Your account is verified. Please login to have full access to our website');
        }

        return $this->redirectToRoute('app_homepage');
    }

    /**
     * @Route("/resend-verification-email", name="app_resend_verification_email")
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     */
    public function resendVerificationEmail(
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
        UserRepository $userRepository,
        ConfirmationEmailSender $confirmationEmailSender
    ): Response {
        $error = null;

        if ('POST' === $request->getMethod()) {
            $email = $request->request->get('email');
            if ($email) {
                $user = $userRepository->findOneBy([
                    'email' => $email,
                ]);
                if ($user) {
                    $signatureComponents = $verifyEmailHelper->generateSignature(
                        'app_verify_email',
                        $user->getId(),
                        $user->getEmail(),
                        ['id' => $user->getId()]
                    );
                    $confirmationEmailSender->sendConfirmationEmail($user, $signatureComponents->getSignedUrl());
                    $this->addFlash('success', 'A verification email was sent');

                    return $this->redirectToRoute('app_homepage');
                }
                $error = 'Email not registered in our database';
            } else {
                $error = 'Please retype your email';
            }
        }

        return $this->render('registration/resend_verification.html.twig', [
            'error' => $error,
        ]);
    }
}
