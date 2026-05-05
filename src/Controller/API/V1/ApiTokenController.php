<?php

namespace App\Controller\API\V1;

use App\Controller\API\BaseApiController;
use App\DataList\ApiToken\ApiTokenDataListConfiguration;
use App\DataList\DataListManager;
use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Rest\Route("/tokens")
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 *
 * @OA\Tag(name="Tokens")
 */
class ApiTokenController extends BaseApiController
{
    /**
     * @Route("", name="api_v1_api_token_list", methods="GET")
     *
     * @Rest\QueryParam(name="identifier", map=true, nullable=true, description="search by identifier")
     * @Rest\QueryParam(name="createdAt",  map=true, nullable=true, description="filter by createdAt")
     * @Rest\QueryParam(name="lastUsedAt", map=true, nullable=true, description="filter by lastUsedAt")
     * @Rest\QueryParam(name="sort", requirements="(asc|desc)", allowBlank=false, default="asc", description="Sort direction")
     * @Rest\QueryParam(name="sort_by", requirements="\w+", default="createdAt", description="Sort by field name")
     * @Rest\QueryParam(name="limit", map=false, requirements="\d+", default=23, description="size of the page")
     * @Rest\QueryParam(name="page", map=false, requirements="\d+", default=1, description="page number")
     *
     * @OA\Get(
     *     summary="List API tokens (paginated)",
     *     security={{"ApiToken":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated token list",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="result", type="array", @OA\Items(ref="#/components/schemas/ApiTokenRead")),
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Not authenticated"),
     *     @OA\Response(response=403, description="Forbidden — requires ROLE_API_TOKEN_READ")
     * )
     */
    public function getSpellList(ParamFetcher $paramFetcher, ApiTokenDataListConfiguration $dataList, DataListManager $manager): JsonResponse
    {
        $tokens = $manager->list($dataList, $paramFetcher->all());

        return $this->json($tokens, Response::HTTP_OK, [], [
            'groups' => ['api:api_token:read', 'api:response:list'],
        ]);
    }

    /**
     * @Route("", name="api_v1_api_token_create", methods="POST")
     *
     * @OA\Post(
     *     summary="Generate an API token",
     *     description="Creates a new API token for the authenticated user, emails it, and returns the plaintext value. Requires a session established via form login (IS_AUTHENTICATED_FULLY).",
     *     security={{"ApiToken":{}}},
     *
     *     @OA\Response(
     *         response=201,
     *         description="Token created",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="plain_token", type="string", example="a1b2c3d4e5f6a7b8.64hexcharsecrethere")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Not authenticated")
     * )
     */
    public function create(ApiTokenRepository $apiTokenRepository): JsonResponse
    {
        $user = $this->getUser();

        $apiToken = new ApiToken($user);
        $plainToken = $apiToken->getPlainToken();

        $apiTokenRepository->add($apiToken, true);

        return $this->json(['plain_token' => $plainToken], Response::HTTP_CREATED);
    }

    /**
     * @Route("/{identifier}", name="api_v1_api_token_revoke", methods="DELETE")
     *
     * @OA\Delete(
     *     summary="Revoke an API token",
     *     description="Permanently deletes the token. Only the token owner can revoke it. Returns 404 whether the token does not exist or belongs to another user (no enumeration).",
     *     security={{"ApiToken":{}}},
     *
     *     @OA\Parameter(name="identifier", in="path", required=true, @OA\Schema(type="string", example="a1b2c3d4e5f6a7b8")),
     *
     *     @OA\Response(response=204, description="Token revoked"),
     *     @OA\Response(response=401, description="Not authenticated"),
     *     @OA\Response(response=404, description="Token not found or not owned by caller")
     * )
     */
    public function revoke(string $identifier, ApiTokenRepository $apiTokenRepository): JsonResponse
    {
        $user = $this->getUser();
        $apiToken = $apiTokenRepository->findOneByIdentifierAndUser($identifier, $user);

        if (null === $apiToken) {
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }

        $apiTokenRepository->remove($apiToken, true);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
