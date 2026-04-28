<?php

namespace App\Security;

use App\Entity\ApiToken;
use App\Messenger\Message\TokenUsed;
use App\Model\Api\Response as ApiResponse;
use App\Repository\ApiTokenRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
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
    private MessageBusInterface $commandBus;
    private CacheItemPoolInterface $apiTokenThrottleCache;

    public function __construct(
        string $authTokenHeader,
        ApiTokenRepository $apiTokenRepository,
        SerializerInterface $serializer,
        MessageBusInterface $commandBus,
        CacheItemPoolInterface $apiTokenThrottleCache
    ) {
        $this->apiTokenRepository = $apiTokenRepository;
        $this->serializer = $serializer;
        $this->authTokenHeader = $authTokenHeader;
        $this->commandBus = $commandBus;
        $this->apiTokenThrottleCache = $apiTokenThrottleCache;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has($this->authTokenHeader)
            && 0 === strpos($request->headers->get($this->authTokenHeader), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->extractRawToken($request);

        $userBadge = new UserBadge($token, function ($token) {
            $parts = explode(ApiToken::DELIMITER, $token, 2);
            if (2 !== \count($parts)) {
                throw new CustomUserMessageAuthenticationException('Invalid token format', [], Response::HTTP_UNAUTHORIZED);
            }
            [$identifier, $secret] = $parts;

            /** @var ApiToken|null $apiToken */
            $apiToken = $this->apiTokenRepository->findOneByIdentifier($identifier);
            if (null === $apiToken || !$apiToken->verifySecret($secret)) {
                throw new CustomUserMessageAuthenticationException('Token not found', [], Response::HTTP_UNAUTHORIZED);
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
        $parts = explode(ApiToken::DELIMITER, $this->extractRawToken($request), 2);
        if (2 === \count($parts)) {
            $identifier = $parts[0];
            $cacheItem = $this->apiTokenThrottleCache->getItem('api_token_used_'.$identifier);
            if (!$cacheItem->isHit()) {
                $this->apiTokenThrottleCache->save($cacheItem->set('1'));
                $this->commandBus->dispatch(new TokenUsed($identifier));
            }
        }

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

    private function extractRawToken(Request $request): string
    {
        return str_ireplace('Bearer ', '', $request->headers->get($this->authTokenHeader, ''));
    }
}
