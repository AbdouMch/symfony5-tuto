<?php

namespace App\Controller\API\V1;

use App\Controller\API\BaseApiController;
use App\DataList\User\UserDataList;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/users")
 *
 * @IsGranted("ROLE_USER")
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
