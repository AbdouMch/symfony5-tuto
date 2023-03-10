<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private UrlGeneratorInterface $urlGenerator;
    // for custom credentials
//    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UrlGeneratorInterface $urlGenerator/* , UserPasswordHasherInterface $passwordHasher */)
    {
        $this->urlGenerator = $urlGenerator;
        // for custom credentials
//        $this->passwordHasher = $passwordHasher;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        // This badge will query the user with the user provider configured in security.yaml
        // or with the second argument callback if provided
        // When the user is found the credentials check with the CredentialsInterface will be called
        $userBadge = new UserBadge($email);

        // here we check if the user provides the correct credentials
//        $userCredential = new CustomCredentials(
//            function ($credentials, User $user) {
//                return $this->passwordHasher->isPasswordValid($user, $credentials);
//            },
//            $password
//        );
        $userCredential = new PasswordCredentials($password);

        return new Passport(
            $userBadge,
            $userCredential,
            [
                new CsrfTokenBadge('authentication', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // get the URL the user last visited before being forced to authenticate
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_homepage'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}
