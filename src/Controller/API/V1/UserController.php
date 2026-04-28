<?php

namespace App\Controller\API\V1;

use App\Controller\API\BaseApiController;
use App\DataList\User\UserDataList;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/users")
 *
 * @IsGranted("ROLE_USER")
 *
 * @OA\Tag(name="Users")
 */
class UserController extends BaseApiController
{
    /**
     * @Route("", name="api_v1_users_list", methods="GET")
     *
     * @Rest\QueryParam(name="id", map=true, nullable=true, description="search by user id")
     * @Rest\QueryParam(name="email", map=true, nullable=true, description="search by user email")
     * @Rest\QueryParam(name="sort", requirements="(asc|desc)", allowBlank=false, default="asc", description="Sort direction")
     * @Rest\QueryParam(name="sort_by", requirements="\w+", default="email", description="Sort by field name")
     * @Rest\QueryParam(name="limit", map=false, requirements="\d+", default=23, description="size of the page")
     * @Rest\QueryParam(name="page", map=false, requirements="\d+", default=1, description="page number")
     *
     * @Rest\View()
     *
     * @OA\Get(
     *     path="/api/v1/users",
     *     summary="List users (paginated)",
     *     tags={"Users"},
     *     security={{"ApiToken":{}}},
     *     @OA\Parameter(name="id[]", in="query", required=false, description="Filter by user ID",
     *         @OA\Schema(type="array", @OA\Items(type="integer"))
     *     ),
     *     @OA\Parameter(name="email[]", in="query", required=false, description="Filter by email",
     *         @OA\Schema(type="array", @OA\Items(type="string", format="email"))
     *     ),
     *     @OA\Parameter(name="sort", in="query", required=false, description="Sort direction",
     *         @OA\Schema(type="string", enum={"asc","desc"}, default="asc")
     *     ),
     *     @OA\Parameter(name="sort_by", in="query", required=false, description="Field to sort by",
     *         @OA\Schema(type="string", default="email")
     *     ),
     *     @OA\Parameter(name="limit", in="query", required=false, description="Page size",
     *         @OA\Schema(type="integer", default=23)
     *     ),
     *     @OA\Parameter(name="page", in="query", required=false, description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated user list",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="array", @OA\Items(ref="#/components/schemas/UserRead")),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="limit", type="integer", example=23),
     *             @OA\Property(property="page", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Not authenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function getUsersList(Request $request, ParamFetcher $paramFetcher, UserDataList $userDataList): View
    {
        $serializerGroups = $request->get('serializer_group', '["api:user"]');
        $serializerGroups = json_decode($serializerGroups);
        $serializerGroups[] = 'api:response:list';
        $context = new Context();
        $context->setGroups($serializerGroups);

        $users = $userDataList->list($paramFetcher);

        return $this->view($users, Response::HTTP_OK)->setContext($context);
    }
}
