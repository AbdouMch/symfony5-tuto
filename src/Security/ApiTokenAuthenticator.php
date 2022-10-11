<?php

namespace App\Security;

use App\Entity\ApiToken;
use App\Model\Api\Response as ApiResponse;
use App\Repository\ApiTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    private ApiTokenRepository $apiTokenRepository;
    private SerializerInterface $serializer;
    private string $authTokenHeader;

    public function __construct(string $authTokenHeader, ApiTokenRepository $apiTokenRepository, SerializerInterface $serializer)
    {
        $this->apiTokenRepository = $apiTokenRepository;
        $this->serializer = $serializer;
        $this->authTokenHeader = $authTokenHeader;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has($this->authTokenHeader)
            && 0 === strpos($request->headers->get($this->authTokenHeader), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $bearerToken = $request->headers->get($this->authTokenHeader);
        $token = str_ireplace('Bearer ', '', $bearerToken);

        $userBadge = new UserBadge($token, function ($token) {
            /** @var ApiToken|null $apiToken */
            $apiToken = $this->apiTokenRepository->findOneBy(['token' => $token]);
            if (null === $apiToken) {
                throw new CustomUserMessageAuthenticationException('Token not found', [], Response::HTTP_UNAUTHORIZED);
            }
            if ($apiToken->isExpired()) {
                throw new CustomUserMessageAuthenticationException('Token expired', [], Response::HTTP_UNAUTHORIZED);
            }

            return $apiToken->getUser();
        });

        $credentialsBadge = new CustomCredentials(function () {
            // here all the token validation checks in the used badge are passed
            return true;
        }, $token);

        return new Passport($userBadge, $credentialsBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // continue to the requested controller
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = $this->serializer->serialize(
            new ApiResponse($exception->getMessageKey(), $exception->getCode()),
            'json',
            [AbstractNormalizer::GROUPS => ['api:response']]
        );

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED, [], true);
    }
}
